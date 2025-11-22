<?php

namespace App\Http\Controllers;

// use GrahamCampbell\ResultType\Success; // Tidak terpakai
use Illuminate\Http\Request;
// use Illuminate\Contracts\View\Factory; // Tidak terpakai
// use Illuminate\Http\JsonResponse; // Tidak terpakai
// use Illuminate\Http\RedirectResponse; // Tidak terpakai
// use Spatie\FlareClient\Http\Exceptions\NotFound; // Tidak terpakai
// use Stripe\Checkout\Session; // Kita tidak pakai Stripe
// use Stripe\Exception\ApiErrorException; // Kita tidak pakai Stripe
// use Symfony\Component\HttpKernel\Exception\NotFoundHttpException; // Tidak terpakai
use App\Models\Donation;
use Illuminate\Support\Facades\Log; // Tambahkan ini untuk logging

class CheckoutController extends Controller
{
    public function __construct()
    {
        // \Stripe\Stripe::setApiKey(env('STRIPE_SECRET')); // <-- DINONAKTIFKAN
    }

    /**
     * Fungsi ini sekarang mensimulasikan donasi yang sukses,
     * melewatkan Stripe, dan langsung mencatat ke database.
     */
    public function createSession(Request $request)
    {
        // 1. Validasi input (aturan lokasi sudah Anda nonaktifkan)
        $request->validate([
            'first_name' => 'required|string|min:3|max:50',
            'last_name' => 'required|string|min:3|max:50',
            'email' => 'required|email',
            'mobile' => 'nullable|string|max:20',
            'amount' => 'required|numeric|min:'.env('MIN_DONATION_AMOUNT',1),
            // 'country' => 'required|numeric',
            // 'state' => 'required|numeric',
            // 'city' => 'required|numeric',
            // 'street_address' => 'nullable|string',
            'add_to_leaderboard' => 'nullable|string|in:yes,no',
        ]);

        try {
            // 2. SIMULASI: Langsung buat data donasi
            // (Semua kode Stripe::create() dihapus)
            
            $donation = new Donation();
            $donation->status = 'paid'; // Langsung set 'paid' karena ini simulasi
            $donation->amount = $request->amount;
            $donation->mobile = $request->mobile;
            $donation->street_address = $request->street_address ?? null; // Gunakan '?? null'
            $donation->country_id = $request->country ?? null; // Gunakan '?? null'
            $donation->state_id = $request->state ?? null; // Gunakan '?? null'
            $donation->city_id = $request->city ?? null; // Gunakan '?? null'
            $donation->email = $request->email;
            $donation->name = $request->first_name. ' ' . $request->last_name;
            $donation->session_id = 'simulasi-' . uniqid(); // Buat ID sesi palsu
            $donation->add_to_leaderboard = $request->add_to_leaderboard ?: 'no';
            $donation->save();

            // ==========================================================
            // 3. !!! TEMPAT LOGIKA BLOCKCHAIN ANDA !!!
            // Panggil smart contract Anda di sini
            // ==========================================================
            try {
               
               // Ganti ini dengan logika/class Anda untuk memanggil smart contract
               // Contoh:
               // MySmartContract::catatDonasi(
               //     $donation->name,
               //     $donation->amount,
               //     now()->timestamp
               // );
               
               Log::info('Simulasi donasi sukses, logika blockchain akan dipanggil di sini.');

            } catch (\Exception $e) {
               // Catat error jika blockchain gagal, tapi jangan hentikan user
               Log::error('Gagal catat ke blockchain: ' . $e->getMessage());
            }
            // ==========================================================


            // 4. Redirect kembali ke halaman donasi dengan pesan sukses
            return redirect('donate')->with(['success' => 'SIMULASI BERHASIL: Donasi senilai ' . $request->amount . ' telah dicatat. (Pembayaran Stripe dilewati)']);

        } catch (\Exception $e) {
            // Jika gagal menyimpan ke database LOKAL
            return redirect()->back()->with(['error' => 'Gagal menyimpan donasi simulasi. [' . $e->getMessage() . ']'])->withInput();
        }

        // Baris "return redirect($session->url);" dihapus karena $session tidak ada lagi
    }

    /**
     * Fungsi ini tidak akan terpakai lagi karena kita melewati Stripe,
     * tapi kita biarkan saja agar rute-nya tidak error.
     */
    public function paymentSuccess(Request $request)
    {
        // $sessionId = $request->get('session_id'); // <-- Dinonaktifkan
        try {
            // Semua logika Stripe::retrieve() dihapus
            return redirect('donate')->with(['success' => 'Terima kasih atas donasi Anda! (Simulasi sukses)']);

        } catch(\Exception $e) {
            return redirect('donate')->with(['error' => 'Something went wrong. [' .$e->getMessage() .']']);
        }
    }

    /**
     * Fungsi ini tidak akan terpakai lagi karena kita melewati Stripe,
     * tapi kita biarkan saja agar rute-nya tidak error.
     */
    public function handleFailedPayment(Request $request)
    {
        // $sessionId = $request->get('session_id'); // <-- Dinonaktifkan
        try {
            // Semua logika Stripe::retrieve() dihapus
            return redirect('donate')->with(['error' => 'Checkout process has been cancelled. (Simulasi gagal)']);

        } catch(\Exception $e) {
            return redirect('donate')->with(['error' => 'Something went wrong. [' .$e->getMessage() .']']);
        }
    }
}