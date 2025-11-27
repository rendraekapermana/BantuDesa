<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Donation;
use App\Http\Controllers\DonationController;
use Illuminate\Support\Facades\Log;
use Midtrans\Config;
use Midtrans\Snap;

class CheckoutController extends Controller
{
    public function __construct()
    {
        Config::$serverKey = config('midtrans.server_key');
        Config::$isProduction = config('midtrans.is_production');
        Config::$isSanitized = config('midtrans.is_sanitized');
        Config::$is3ds = config('midtrans.is_3ds');
    }

    public function createSession(Request $request)
    {
        $request->validate([
            'first_name' => 'required|string|min:3|max:50',
            'last_name' => 'required|string|min:3|max:50',
            'email' => 'required|email',
            'mobile' => 'nullable|string|max:20',
            'amount' => 'required|numeric|min:' . env('MIN_DONATION_AMOUNT', 1000),
            'street_address' => 'nullable|string',
            'country_name' => 'nullable|string',
            'state_name' => 'nullable|string',
            'city_name' => 'nullable|string',
            'add_to_leaderboard' => 'nullable|string|in:yes,no',
        ]);

        try {
            $donation = new Donation();
            $donation->status = 'unpaid';
            $donation->amount = $request->amount;
            $donation->mobile = $request->mobile;
            $donation->street_address = $request->street_address ?? null;
            $donation->email = $request->email;
            $donation->name = $request->first_name . ' ' . $request->last_name;
            $donation->session_id = 'MIDTRANS-' . uniqid();
            $donation->add_to_leaderboard = $request->add_to_leaderboard ?: 'no';
            $donation->save();

            $grossAmount = (int) $request->amount;

            $transactionDetails = [
                'order_id' => $donation->session_id,
                'gross_amount' => $grossAmount,
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

            $transactionData = [
                'transaction_details' => $transactionDetails,
                'customer_details' => $customerDetails,
                'item_details' => [
                    [
                        'id' => 'DONATION-' . $donation->id,
                        'price' => $grossAmount,
                        'quantity' => 1,
                        'name' => 'Donasi untuk ' . env('APP_NAME', 'BantuDesa'),
                    ]
                ],
                'callbacks' => [
                    'finish' => route('midtrans.finish'),
                    'error' => route('midtrans.error'),
                    'pending' => route('midtrans.pending'),
                ]
            ];

            $snapToken = Snap::getSnapToken($transactionData);

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


    public function handleCallback(Request $request)
    {
        try {
            $notif = new \Midtrans\Notification();

            $transactionStatus = $notif->transaction_status;
            $orderId = $notif->order_id;
            $fraudStatus = $notif->fraud_status;

            Log::info('Midtrans Callback Masuk:', ['order_id' => $orderId, 'status' => $transactionStatus]);

            $donation = Donation::where('session_id', $orderId)->first();

            if (!$donation) {
                Log::error('Donation not found for order_id: ' . $orderId);
                return response()->json(['message' => 'Donation not found'], 404);
            }

            $isPaid = false;

            if ($transactionStatus == 'capture') {
                if ($fraudStatus == 'accept') {
                    $donation->status = 'paid';
                    $isPaid = true;
                }
            } else if ($transactionStatus == 'settlement') {
                $donation->status = 'paid';
                $isPaid = true;
            } else if ($transactionStatus == 'pending') {
                $donation->status = 'unpaid';
            } else if (in_array($transactionStatus, ['deny', 'expire', 'cancel'])) {
                $donation->status = 'failed';
            }

            $donation->save();

            if ($isPaid && $donation->blockchain_tx_hash == null) {
                Log::info("Pembayaran Lunas. Menjalankan proses blockchain...");

                DonationController::runBlockchainProcess($donation);
            }

            return response()->json(['message' => 'Notification handled']);
        } catch (\Exception $e) {
            Log::error('Midtrans Callback Error: ' . $e->getMessage());
            return response()->json(['message' => 'Error processing notification'], 500);
        }
    }

    public function finishPayment(Request $request)
    {
        $orderId = $request->order_id;
        $transactionStatus = $request->transaction_status;

        Log::info('Finish Payment Redirect:', ['order_id' => $orderId, 'status' => $transactionStatus]);

        $donation = Donation::where('session_id', $orderId)->first();

        if ($donation) {
            $errorMessage = null;

            if ($transactionStatus == 'settlement' || $transactionStatus == 'capture') {
                $donation->status = 'paid';
                $donation->save();

                if ($donation->blockchain_tx_hash == null) {
                    Log::info("Menjalankan blockchain via Finish Redirect...");

                    $result = DonationController::runBlockchainProcess($donation);

                    $donation->refresh();

                    if ($result !== true && is_array($result) && isset($result['error'])) {
                        $errorMessage = "Error Blockchain: " . $result['error'];
                    }
                }
            }

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
