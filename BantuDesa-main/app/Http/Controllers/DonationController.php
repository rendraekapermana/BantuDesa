<?php

namespace App\Http\Controllers;

use App\Models\Contact;
use Illuminate\Http\Request;
use App\Models\Donation;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Symfony\Component\Process\Process;
use Symfony\Component\Process\Exception\ProcessFailedException;

class DonationController extends Controller
{
    // ... (Fungsi donation, leaderBoardStatus, delete BIARKAN SAMA) ...
    
    public function donation(Request $req)
    {
        if ($req->ajax()) {
            if (auth()->user()->role !== 'admin')
                return response()->json(['error' => 'You\'re not authorized'], 401);
            $donation = Donation::query();
            if ($req->status) $donation->where('status', $req->status);
            $donation->select('*')->get();
            return \DataTables::of($donation)
                ->addColumn('action', function ($donation) {
                    $button = '<button type="button" data-id="' . $donation->id . '" class="edit btn btn-outline-danger btn-sm mb-1 me-1 deletedonation"><i class="fa fa-trash"></i></button>';
                    if ($donation->add_to_leaderboard == 'yes') $button .= ' <button type="button" data-id="' . $donation->id . '" data-status="' . $donation->add_to_leaderboard . '" class="btn btn-outline-danger btn-sm mb-1 me-1 change-leaderboard-status"><i class="fa fa-ban"></i></button>';
                    else $button .= ' <button type="button" data-id="' . $donation->id . '" data-status="' . $donation->add_to_leaderboard . '" class="btn btn-outline-success btn-sm mb-1 me-1 change-leaderboard-status"><i class="fa fa-check"></i></button>';
                    return $button;
                })
                ->rawColumns(['action'])->make(true);
        }
        if (auth()->user()->role !== 'admin') return redirect()->route('auth.dashboard')->with(['error' => 'Oops! You are not authorized to access this page.']);
        return view('admin.auth.donation');
    }

    public function leaderBoardStatus(Request $req) {
        if (auth()->user()->role !== 'admin') return response()->json(['error' => 'You\'re not authorized.'], 401);
        $req->validate(['id' => 'required|exists:donation,id', 'status' => 'required|in:yes,no']);
        $donation = Donation::where('id', $req->id)->first();
        $donation->add_to_leaderboard = $req->status == "yes" ? "no" : "yes";
        $donation->save();
        return response()->json(['success' => 'LeaderBoard status changed successfully'], 200);
    }

    public function delete(Request $req) {
        if (auth()->user()->role !== 'admin') return response()->json(['error' => 'You\'re not authorized.'], 401);
        $req->validate(['id' => 'required|exists:donation,id']);
        $donation = Donation::where('id', $req->id)->first();
        if ($donation->delete()) return response()->json(['success' => 'Donation deleted successfully'], 200);
        return response()->json(['error' => 'Something went wrong'], 500);
    }

    // ========================================================================
    // LOGIC BLOCKCHAIN SERVER-SIDE (FIXED ASSERTION ERROR)
    // ========================================================================

    public function processDonation(Request $request)
    {
        $request->validate([
            'amount' => 'required|numeric|min:1000',
            'donor_name' => 'required|string|max:255',
            'email' => 'required|email',
        ]);

        try {
            // 1. Simpan Database
            $donationData = [
                'name' => $request->donor_name,
                'amount' => $request->amount,
                'email' => $request->email,
                'mobile' => $request->mobile ?? null,
                'street_address' => $request->street_address ?? null,
                'country_id' => $request->country_id ?? null,
                'state_id' => $request->state_id ?? null,
                'city_id' => $request->city_id ?? null,
                'user_id' => auth()->check() ? auth()->id() : null,
                'add_to_leaderboard' => $request->has('add_to_leaderboard') ? 'yes' : 'no',
                'session_id' => 'TEMP_' . uniqid(),
                'status' => 'pending_onchain',
                'blockchain_tx_hash' => null,
                'donor_wallet_address' => null,
            ];

            $donation = Donation::create($donationData);

            // 2. Konfigurasi Path
            $npxExecutable = "C:\\Program Files\\nodejs\\npx.cmd";
            $projectRoot = dirname(base_path()); 
            $workingDir = $projectRoot . DIRECTORY_SEPARATOR . 'BantuDesa-blockchain';
            $scriptRelativePath = 'scripts/relay-donation.js'; 
            
            $contractAddress = env('BLOCKCHAIN_CONTRACT_ADDRESS'); 
            if (empty($contractAddress)) {
                throw new \Exception("BLOCKCHAIN_CONTRACT_ADDRESS belum di-set di .env Laravel.");
            }

            // 3. Siapkan Process
            $process = new Process([$npxExecutable, 'hardhat', 'run', $scriptRelativePath, '--network', 'sepolia']);
            
            $process->setWorkingDirectory($workingDir);
            $process->setTimeout(300);

            // [FIX 1] Matikan Input Stream agar Node.js tidak mencoba membacanya dan crash
            $process->setInput(null);

            // 4. Set Env (FIX CRITICAL)
            // Menambahkan CI=true dan FORCE_COLOR=0 mencegah Hardhat merender elemen interaktif yang bikin crash di Windows pipe
            $process->setEnv([
                'OPENSSL_CONF' => null, 
                'CI' => 'true',            // Penting: Mode Non-Interaktif
                'FORCE_COLOR' => '0',      // Penting: Matikan warna terminal
                'NO_COLOR' => '1',         // Penting: Matikan warna terminal
                'DONATION_ID' => (string)$donation->id,
                'AMOUNT_RUPIAH' => (string)$donation->amount,
                'CONTRACT_ADDRESS' => $contractAddress, 
                'SystemRoot' => getenv('SystemRoot'),
                'PATH' => getenv('PATH'),
            ]);

            $process->run();

            if (!$process->isSuccessful()) {
                Log::error('Blockchain Error Output: ' . $process->getErrorOutput());
                // Log Standard output juga karena kadang error muncul di stdout bukan stderr
                Log::error('Blockchain Standard Output: ' . $process->getOutput());
                
                $donation->update(['status' => 'failed_onchain']);
                throw new \Exception("Script Blockchain Error (Cek Log). Error: " . $process->getErrorOutput());
            }

            // 5. Tangkap Output
            $output = $process->getOutput();
            $txHash = null;
            $walletAddr = null;

            if (preg_match('/SUCCESS_HASH:(0x[a-fA-F0-9]+)/', $output, $matches)) {
                $txHash = $matches[1];
            }
            
            if (preg_match('/SUCCESS_ADDR:(0x[a-fA-F0-9]+)/', $output, $matchesAddr)) {
                $walletAddr = $matchesAddr[1];
            }

            if ($txHash) {
                $donation->update([
                    'status' => 'recorded_on_chain',
                    'blockchain_tx_hash' => $txHash,
                    'donor_wallet_address' => $walletAddr ?? 'Admin Wallet (Relayer)' 
                ]);

                return redirect()->route('donation.complete', ['id' => $donation->id])
                    ->with('success', 'Donasi berhasil dicatat di jaringan Sepolia Testnet!');
            } else {
                Log::error("Node Output: " . $output);
                throw new \Exception("Hash tidak ditemukan di output script. Output: " . $output);
            }

        } catch (\Exception $e) {
            Log::error('Donation Process Error: ' . $e->getMessage());
            return redirect()->back()->with(['error' => 'Terjadi kesalahan: ' . $e->getMessage()])->withInput();
        }
    }

    public function donationComplete($id)
    {
        $donation = Donation::where('id', $id)->firstOrFail();
        return view('donation_complete', compact('donation'));
    }
}