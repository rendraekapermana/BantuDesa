<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Donation;
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
        ]);

        try {
            // 2. Buat record donasi dengan status pending
            $donation = new Donation();
            $donation->status = 'unpaid'; // Status awal
            $donation->amount = $request->amount;
            $donation->mobile = $request->mobile;
            $donation->street_address = $request->street_address ?? null;
            $donation->email = $request->email;
            $donation->name = $request->first_name. ' ' . $request->last_name;
            $donation->session_id = 'MIDTRANS-' . uniqid(); // Generate unique session ID
            $donation->add_to_leaderboard = $request->add_to_leaderboard ?: 'no';
            $donation->save();

            // 3. Prepare transaction data untuk Midtrans
            $transactionDetails = [
                'order_id' => $donation->session_id,
                'gross_amount' => (int) $request->amount, // Midtrans membutuhkan integer
            ];

            $customerDetails = [
                'first_name' => $request->first_name,
                'last_name' => $request->last_name,
                'email' => $request->email,
                'phone' => $request->mobile ?? '',
            ];

            // Optional: Tambahkan billing address jika ada
            if ($request->street_address) {
                $customerDetails['billing_address'] = [
                    'address' => $request->street_address,
                    'city' => $request->city_name ?? '',
                    'country_code' => 'IDN', // Sesuaikan dengan negara
                ];
            }

            $transactionData = [
                'transaction_details' => $transactionDetails,
                'customer_details' => $customerDetails,
                'item_details' => [
                    [
                        'id' => 'DONATION-' . $donation->id,
                        'price' => (int) $request->amount,
                        'quantity' => 1,
                        'name' => 'Donasi untuk ' . env('APP_NAME'),
                    ]
                ],
                'callbacks' => [
                    'finish' => route('midtrans.finish'),
                    'error' => route('midtrans.error'),
                    'pending' => route('midtrans.pending'),
                ]
            ];

            // 4. Dapatkan Snap Token dari Midtrans
            $snapToken = Snap::getSnapToken($transactionData);
            
            // 5. Simpan snap token ke donation record
            $donation->update(['session_id' => $snapToken]);

            // 6. Return view dengan snap token untuk client-side
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
     * Handle callback dari Midtrans setelah pembayaran selesai
     */
    public function handleCallback(Request $request)
    {
        try {
            // Ambil notification dari Midtrans
            $notif = new \Midtrans\Notification();

            $transactionStatus = $notif->transaction_status;
            $orderId = $notif->order_id;
            $fraudStatus = $notif->fraud_status;

            Log::info('Midtrans Callback: ', [
                'order_id' => $orderId,
                'status' => $transactionStatus,
                'fraud' => $fraudStatus
            ]);

            // Cari donation berdasarkan order_id (session_id)
            $donation = Donation::where('session_id', $orderId)->first();

            if (!$donation) {
                return response()->json(['message' => 'Donation not found'], 404);
            }

            // Update status berdasarkan transaction_status
            if ($transactionStatus == 'capture') {
                if ($fraudStatus == 'accept') {
                    $donation->status = 'paid';
                }
            } else if ($transactionStatus == 'settlement') {
                $donation->status = 'paid';
            } else if ($transactionStatus == 'pending') {
                $donation->status = 'unpaid';
            } else if (in_array($transactionStatus, ['deny', 'expire', 'cancel'])) {
                $donation->status = 'failed';
            }

            $donation->save();

            return response()->json(['message' => 'Notification handled']);

        } catch (\Exception $e) {
            Log::error('Midtrans Callback Error: ' . $e->getMessage());
            return response()->json(['message' => 'Error processing notification'], 500);
        }
    }

    /**
     * Halaman setelah user finish dari Midtrans Snap
     */
    public function finishPayment(Request $request)
    {
        $orderId = $request->order_id;
        $statusCode = $request->status_code;
        $transactionStatus = $request->transaction_status;

        $donation = Donation::where('session_id', $orderId)->first();

        if ($donation && $transactionStatus == 'settlement') {
            $donation->status = 'paid';
            $donation->save();

            // Redirect ke halaman konfirmasi blockchain
            return redirect()->route('donation.confirm_onchain', ['id' => $donation->id])
                ->with(['success' => 'Pembayaran berhasil! Silakan konfirmasi pencatatan blockchain.']);
        }

        return redirect('donate')
            ->with(['success' => 'Pembayaran Anda sedang diproses. Silakan cek email untuk konfirmasi.']);
    }

    /**
     * Halaman error payment
     */
    public function errorPayment(Request $request)
    {
        return redirect('donate')
            ->with(['error' => 'Pembayaran gagal atau dibatalkan.']);
    }

    /**
     * Halaman pending payment
     */
    public function pendingPayment(Request $request)
    {
        return redirect('donate')
            ->with(['info' => 'Pembayaran Anda sedang pending. Silakan selesaikan pembayaran.']);
    }
}