<?php

namespace App\Http\Controllers;

use App\Models\Contact;
use Illuminate\Http\Request;
use App\Models\Donation;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
// Pastikan use statements untuk Models Anda yang lain ada di sini
// use App\Models\Albums;
// use App\Models\City;
// use App\Models\Country;
// use App\Models\State;
// ... dll.

class DonationController extends Controller
{
    // ========================================================================
    // LOGIC ADMINISTRATOR (Logika yang sudah ada)
    // ========================================================================

    public function donation(Request $req)
    {
        if ($req->ajax()) {
            if (auth()->user()->role !== 'admin')
                return response()->json(['error' => 'You\'re not authorized'], 401);

            $donation = Donation::query();
            if ($req->status) {
                $donation->where('status', $req->status);
            }
            $donation->select('*')->get();

            return \DataTables::of($donation)
                ->addColumn('action', function ($donation) {
                    $button = '<button type="button" data-id="' . $donation->id . '" class="edit btn btn-outline-danger btn-sm mb-1 me-1 deletedonation"><i class="fa fa-trash"></i></button>';
                    if ($donation->add_to_leaderboard == 'yes')
                        $button .= ' <button type="button" data-id="' . $donation->id . '" data-status="' . $donation->add_to_leaderboard . '" class="btn btn-outline-danger btn-sm mb-1 me-1 change-leaderboard-status"><i class="fa fa-ban"></i></button>';
                    else
                        $button .= ' <button type="button" data-id="' . $donation->id . '" data-status="' . $donation->add_to_leaderboard . '" class="btn btn-outline-success btn-sm mb-1 me-1 change-leaderboard-status"><i class="fa fa-check"></i></button>';
                    return $button;
                })
                ->rawColumns(['action'])
                ->make(true);
        }

        if (auth()->user()->role !== 'admin')
            return redirect()->route('auth.dashboard')->with(['error' => 'Oops! You are not authorized to access this page.']);

        return view('admin.auth.donation');
    }

    public function leaderBoardStatus(Request $req)
    {
        if (auth()->user()->role !== 'admin')
            return response()->json(['error' => 'You\'re not authorized.'], 401);

        $req->validate([
            'id' => 'required|exists:donation,id',
            'status' => 'required|in:yes,no',
        ]);

        $donation = Donation::where('id', $req->id)->first();
        $donation->add_to_leaderboard = $req->status == "yes" ? "no" : "yes";
        $donation->save();
        if ($donation->save())
            return response()->json(['success' => 'LeaderBoard status changed successfully'], 200);

        return response()->json(['error' => 'Something went wrong'], 500);
    }

    public function delete(Request $req)
    {
        if (auth()->user()->role !== 'admin')
            return response()->json(['error' => 'You\'re not authorized.'], 401);

        $req->validate([
            'id' => 'required|exists:donation,id',
        ]);

        $donation = Donation::where('id', $req->id)->first();

        if ($donation->delete())
            return response()->json(['success' => 'Donation deleted successfully'], 200);

        return response()->json(['error' => 'Something went wrong'], 500);
    }

    // ========================================================================
    // LOGIC BLOCKCHAIN BARU (processDonation DIKOREKSI ULANG)
    // ========================================================================

    public function processDonation(Request $request)
    {
        $request->validate([
            'amount' => 'required|numeric|min:1000',
            'donor_name' => 'required|string|max:255', // Ini nama input form (biarkan donor_name)
            'email' => 'required|email',
        ]);

        try {
            $donationData = [
                // --- PERBAIKAN DI SINI ---
                // Database butuh kolom 'name', kita ambil dari input 'donor_name' form
                'name' => $request->donor_name,
                // -------------------------

                'amount' => $request->amount,
                'email' => $request->email,
                'mobile' => $request->mobile ?? null,
                'street_address' => $request->street_address ?? null,
                'country_id' => $request->country_id ?? null,
                'state_id' => $request->state_id ?? null,
                'city_id' => $request->city_id ?? null,

                'user_id' => auth()->check() ? auth()->id() : null,
                'status' => 'pending_onchain_record',
                'add_to_leaderboard' => $request->has('add_to_leaderboard') ? 'yes' : 'no',
                'session_id' => 'TEMP_' . uniqid(),
                'blockchain_tx_hash' => null,
                'donor_wallet_address' => null,
            ];

            // 2. Tambahkan/Timpa Kolom Internal dan Wajib (NOT NULL)

            // Kolom wajib oleh DB/Model:
            $donationData['user_id'] = auth()->check() ? auth()->id() : null;
            $donationData['status'] = 'pending_onchain_record';
            $donationData['add_to_leaderboard'] = $request->has('add_to_leaderboard') ? 'yes' : 'no';

            // V V V PERBAIKAN KRITIS UNTUK ERROR SESSION_ID V V V
            // Kita harus menyediakan nilai default unik.
            $donationData['session_id'] = 'TEMP_' . uniqid();
            // ^ ^ ^ PERBAIKAN KRITIS ^ ^ ^

            // Kolom yang mungkin tidak terkirim dari form, tapi harus diisi NULL (jika kolom di DB Anda NULLABLE)
            // Jika kolom-kolom ini adalah NOT NULL di DB, Anda HARUS menyediakannya.
            $donationData['mobile'] = $donationData['mobile'] ?? null;
            $donationData['street_address'] = $donationData['street_address'] ?? null;

            // Pastikan semua kolom yang ada di tabel 'donation' dan terdaftar di Model $fillable TERPENUHI.

            $donation = Donation::create($donationData);
            // 3. Alihkan pengguna ke halaman konfirmasi Metamask
            return redirect()->route('donation.confirm_onchain', ['id' => $donation->id]);
        } catch (\Exception $e) {
            Log::error('Donation Creation Error: ' . $e->getMessage());
            return redirect()->back()->with(['error' => 'Gagal membuat donasi: ' . $e->getMessage()])->withInput();
        }
    }

    public function confirmOnChain($id)
    {
        $donation = Donation::where('id', $id)->firstOrFail();

        // 1. Ambil Path ABI (Sesuaikan path ini dengan folder Hardhat Anda)
        // Jika folder Hardhat sejajar dengan folder Laravel Anda:
        $abiPath = storage_path('app/contracts/DonationTracker.json');

        if (!File::exists($abiPath)) {
            Log::error("Blockchain ABI file not found at: " . $abiPath);
            return redirect('/donate')->with('error', 'Konfigurasi blockchain server error. File kontrak tidak ditemukan.');
        }

        // 2. Baca file JSON dan ambil array ABI
        $abiJson = File::get($abiPath);
        $abi = json_decode($abiJson, true)['abi'];

        // 3. Ambil Contract Address dan CSRF Token
        $contractAddress = env('BLOCKCHAIN_CONTRACT_ADDRESS');

        // 4. Kirim data ke tampilan
        return view('confirm_onchain', [
            'donation' => $donation,
            'contractAddress' => $contractAddress,
            'abi' => $abi,
            'apiUrl' => route('web3.recordSuccess'),
            'csrfToken' => csrf_token()
        ]);
    }

    public function donationComplete($id)
    {
        $donation = Donation::where('id', $id)->firstOrFail();

        return view('donation_complete', compact('donation'));
    }
}
