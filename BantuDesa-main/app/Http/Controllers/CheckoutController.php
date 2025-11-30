<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Donation;
use App\Models\Campaign; // [BARU] Import Model Campaign
use App\Http\Controllers\DonationController; 
use Illuminate\Support\Facades\Log;
use Midtrans\Config;
use Midtrans\Snap;

class CheckoutController extends Controller
{
    public function __construct()
    {
        // Set konfigurasi Midtrans
        Config::$serverKey = config('midtrans.server_key');
        Config::$isProduction = config('midtrans.is_production');
        Config::$isSanitized = config('midtrans.is_sanitized');
        Config::$is3ds = config('midtrans.is_3ds');
    }

    /**
     * Buat transaksi Midtrans dan redirect ke Snap
     */
    public function createSession(Request $request)
    {
        // 1. Validasi input
        $request->validate([
            'first_name' => 'required|string|min:3|max:50',
            'last_name' => 'required|string|min:3|max:50',
            'email' => 'required|email',
            'mobile' => 'nullable|string|max:20',
            'amount' => 'required|numeric|min:'.env('MIN_DONATION_AMOUNT',1000),
            'street_address' => 'nullable|string',
            'country_name' => 'nullable|string',
            'state_name' => 'nullable|string',
            'city_name' => 'nullable|string',
            'add_to_leaderboard' => 'nullable|string|in:yes,no',
            'campaign_id' => 'nullable|exists:campaigns,id', // Validasi Campaign ID
        ]);

        try {
            // 2. Buat record donasi dengan status unpaid
            $donation = new Donation();
            $donation->status = 'unpaid'; 
            $donation->amount = $request->amount;
            $donation->mobile = $request->mobile;
            $donation->street_address = $request->street_address ?? null;
            $donation->email = $request->email;
            $donation->name = $request->first_name. ' ' . $request->last_name;
            // Simpan Order ID yang konsisten
            $donation->session_id = 'MIDTRANS-' . uniqid(); 
            $donation->add_to_leaderboard = $request->add_to_leaderboard ?: 'no';
            
            // [BARU] Simpan Campaign ID
            if($request->has('campaign_id')) {
                $donation->campaign_id = $request->campaign_id;
            }

            $donation->save();

            // [FIX] Pastikan gross_amount dikirim sebagai integer
            $grossAmount = (int) $request->amount;

            // 3. Prepare transaction data untuk Midtrans
            $transactionDetails = [
                'order_id' => $donation->session_id, // Gunakan session_id yang baru dibuat
                'gross_amount' => $grossAmount,      // Pastikan Integer
            ];

            $customerDetails = [
                'first_name' => $request->first_name,
                'last_name' => $request->last_name,
                'email' => $request->email,
                'phone' => $request->mobile ?? '',
            ];

            if ($request->street_address) {
                $customerDetails['billing_address'] = [
                    'address' => $request->street_address,
                    'city' => $request->city_name ?? '',
                    'country_code' => 'IDN', 
                ];
            }

            // Nama Item Dinamis untuk Midtrans
            $itemName = 'Donasi Umum';
            if ($donation->campaign_id) {
                $itemName = 'Donasi #' . $donation->campaign_id;
            }

            $transactionData = [
                'transaction_details' => $transactionDetails,
                'customer_details' => $customerDetails,
                'item_details' => [
                    [
                        'id' => 'DONATION-' . $donation->id,
                        'price' => $grossAmount, // Pastikan Integer
                        'quantity' => 1,
                        'name' => substr($itemName, 0, 50), // Batasi panjang nama item midtrans
                    ]
                ],
                'callbacks' => [
                    'finish' => route('midtrans.finish'),
                    'error' => route('midtrans.error'),
                    'pending' => route('midtrans.pending'),
                ]
            ];

            // 4. Dapatkan Snap Token
            $snapToken = Snap::getSnapToken($transactionData);
            
            // 6. Return view
            return view('midtrans_payment', [
                'snapToken' => $snapToken,
                'donation' => $donation,
                'clientKey' => config('midtrans.client_key')
            ]);

        } catch (\Exception $e) {
            Log::error('Midtrans Error: ' . $e->getMessage());
            return redirect()->back()
                ->with(['error' => 'Gagal memproses pembayaran. [' . $e->getMessage() . ']'])
                ->withInput();
        }
    }

    /**
     * Fungsi Helper Private untuk Update Saldo Campaign dengan aman
     * Mencegah double counting dengan mengecek status lama
     */
    private function updateCampaignBalance(Donation $donation, $transactionStatus)
    {
        // Pastikan status transaksi sukses
        if ($transactionStatus == 'capture' || $transactionStatus == 'settlement') {
            
            // Cek apakah donasi ini sudah pernah dihitung (status sudah paid/recorded)
            // Kita hanya update saldo jika status sebelumnya ADALAH 'unpaid' atau 'pending'
            if ($donation->status == 'unpaid' || $donation->status == 'pending') {
                
                if ($donation->campaign_id) {
                    $campaign = Campaign::find($donation->campaign_id);
                    if ($campaign) {
                        $campaign->current_amount += $donation->amount;
                        $campaign->save();
                        Log::info("SALDO UPDATE: Campaign {$campaign->id} bertambah Rp {$donation->amount}. Total: {$campaign->current_amount}");
                    }
                }
                
                // Update status donasi jadi paid
                $donation->status = 'paid';
                $donation->save();
                return true; // Return true jika saldo baru saja diupdate
            }
        }
        return false; // Tidak ada update saldo (mungkin sudah diupdate sebelumnya)
    }

    /**
     * Handle callback dari Midtrans (Webhook)
     */
    public function handleCallback(Request $request)
    {
        try {
            $notif = new \Midtrans\Notification();

            $transactionStatus = $notif->transaction_status;
            $orderId = $notif->order_id;
            $fraudStatus = $notif->fraud_status;

            Log::info('Midtrans Callback Masuk:', ['order_id' => $orderId, 'status' => $transactionStatus]);

            // Cari donation berdasarkan session_id
            $donation = Donation::where('session_id', $orderId)->first();

            if (!$donation) {
                Log::error('Donation not found for order_id: ' . $orderId);
                return response()->json(['message' => 'Donation not found'], 404);
            }

            // 1. Coba Update Saldo Campaign (Menggunakan helper private di atas)
            // Fungsi ini akan otomatis mengecek apakah statusnya 'unpaid' sebelum menambah saldo.
            // Jadi aman dipanggil berkali-kali.
            $balanceUpdated = $this->updateCampaignBalance($donation, $transactionStatus);

            // Jika status bukan sukses, update status gagal/pending biasa
            if (!in_array($transactionStatus, ['capture', 'settlement'])) {
                 if ($transactionStatus == 'pending') {
                    $donation->status = 'unpaid';
                } else if (in_array($transactionStatus, ['deny', 'expire', 'cancel'])) {
                    $donation->status = 'failed';
                }
                $donation->save();
            }

            // 2. Trigger Blockchain jika donasi sukses (status 'paid') DAN belum ada hash
            if ($donation->status == 'paid' && $donation->blockchain_tx_hash == null) {
                Log::info("Pembayaran Lunas (Webhook). Menjalankan proses blockchain...");
                DonationController::runBlockchainProcess($donation);
            }

            return response()->json(['message' => 'Notification handled']);

        } catch (\Exception $e) {
            Log::error('Midtrans Callback Error: ' . $e->getMessage());
            return response()->json(['message' => 'Error processing notification'], 500);
        }
    }

    /**
     * Halaman Finish (Redirect dari Frontend Midtrans)
     */
    public function finishPayment(Request $request)
    {
        $orderId = $request->order_id; 
        $transactionStatus = $request->transaction_status;

        Log::info('Finish Payment Redirect:', ['order_id' => $orderId, 'status' => $transactionStatus]);

        $donation = Donation::where('session_id', $orderId)->first();

        if ($donation) {
            $errorMessage = null;

            // 1. Coba Update Saldo Campaign (Menggunakan helper yang sama)
            // Ini berguna jika Webhook LAMBAT atau GAGAL. Redirect ini akan menjadi backup untuk update saldo.
            $this->updateCampaignBalance($donation, $transactionStatus);

            // 2. Fallback trigger blockchain jika webhook terlambat
            // Hanya jika status sudah 'paid' tapi hash belum ada
            if (($donation->status == 'paid' || $donation->status == 'recorded_on_chain') && $donation->blockchain_tx_hash == null) {
                
                Log::info("Menjalankan blockchain via Finish Redirect...");
                
                $result = DonationController::runBlockchainProcess($donation);
                
                $donation->refresh();

                if ($result !== true && is_array($result) && isset($result['error'])) {
                    $errorMessage = "Error Blockchain: " . $result['error'];
                }
            }

            // Redirect Logic
            if ($donation->blockchain_tx_hash) {
                return redirect()->route('donation.complete', ['id' => $donation->id])
                    ->with(['success' => 'Pembayaran & Pencatatan Blockchain Berhasil!']);
            } 
            
            if ($donation->status == 'paid_onchain_failed') {
                 return redirect()->route('donation.complete', ['id' => $donation->id])
                    ->with(['error' => 'Pembayaran diterima, tapi gagal catat blockchain. Detail: ' . ($errorMessage ?? 'Cek log server.')]);
            }

            if ($donation->status == 'paid') {
                 return redirect()->route('donation.complete', ['id' => $donation->id])
                    ->with(['info' => 'Pembayaran diterima. Pencatatan blockchain sedang diproses di latar belakang.']);
            }
        }

        Log::error('Finish Payment: Donasi tidak ditemukan atau status invalid untuk ID ' . $orderId);
        
        return redirect('donate')
            ->with(['error' => 'Transaksi tidak ditemukan atau belum selesai.']);
    }

    public function errorPayment(Request $request)
    {
        return redirect('donate')->with(['error' => 'Pembayaran gagal atau dibatalkan.']);
    }

    public function pendingPayment(Request $request)
    {
        return redirect('donate')->with(['info' => 'Pembayaran Anda sedang pending.']);
    }
}