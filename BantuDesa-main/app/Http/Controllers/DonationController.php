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

    public function leaderBoardStatus(Request $req)
    {
        if (auth()->user()->role !== 'admin') return response()->json(['error' => 'You\'re not authorized.'], 401);
        $req->validate(['id' => 'required|exists:donation,id', 'status' => 'required|in:yes,no']);
        $donation = Donation::where('id', $req->id)->first();
        $donation->add_to_leaderboard = $req->status == "yes" ? "no" : "yes";
        $donation->save();
        return response()->json(['success' => 'LeaderBoard status changed successfully'], 200);
    }

    public function delete(Request $req)
    {
        if (auth()->user()->role !== 'admin') return response()->json(['error' => 'You\'re not authorized.'], 401);
        $req->validate(['id' => 'required|exists:donation,id']);
        $donation = Donation::where('id', $req->id)->first();
        if ($donation->delete()) return response()->json(['success' => 'Donation deleted successfully'], 200);
        return response()->json(['error' => 'Something went wrong'], 500);
    }

    public static function runBlockchainProcess(Donation $donation)
    {
        try {
            $npxExecutable = "C:\\Program Files\\nodejs\\npx.cmd";

            $projectRoot = dirname(base_path());
            $workingDir = $projectRoot . DIRECTORY_SEPARATOR . 'BantuDesa-blockchain';
            $scriptRelativePath = 'scripts/relay-donation.js';

            $contractAddress = env('BLOCKCHAIN_CONTRACT_ADDRESS');
            if (empty($contractAddress)) {
                Log::error("Blockchain Error: BLOCKCHAIN_CONTRACT_ADDRESS kosong di .env Laravel");
                $donation->update(['status' => 'paid_onchain_failed']);
                return ['error' => 'Konfigurasi Contract Address hilang.'];
            }

            Log::info("Blockchain: Menjalankan script untuk ID {$donation->id}");

            $process = new Process([$npxExecutable, '--yes', 'hardhat', 'run', $scriptRelativePath, '--network', 'sepolia']);
            $process->setWorkingDirectory($workingDir);
            $process->setTimeout(300); // 5 menit

            $envPath = getenv('PATH') . ';C:\Program Files\nodejs';

            $amountInteger = (string) ((int) $donation->amount);

            $process->setEnv([
                'OPENSSL_CONF' => 'NUL',
                'CI' => 'true',
                'FORCE_COLOR' => '0',
                'DONATION_ID' => (string)$donation->id,
                'AMOUNT_RUPIAH' => $amountInteger,
                'CONTRACT_ADDRESS' => $contractAddress,
                'SystemRoot' => getenv('SystemRoot'),
                'PATH' => $envPath,
            ]);

            $process->run();

            $output = $process->getOutput();
            $errorOutput = $process->getErrorOutput();

            Log::info("Blockchain Output: " . $output);
            if ($errorOutput) Log::error("Blockchain Error/Warning: " . $errorOutput);

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
                    'donor_wallet_address' => $walletAddr ?? 'Admin Relayer'
                ]);
                return true;
            } else {
                $donation->update(['status' => 'paid_onchain_failed']);
                return ['error' => "Gagal dapat Hash. Output: " . substr($output . " " . $errorOutput, 0, 200)];
            }
        } catch (\Exception $e) {
            Log::error('Blockchain Exception: ' . $e->getMessage());
            return ['error' => $e->getMessage()];
        }
    }

    public function processDonation(Request $request)
    {
        return redirect()->route('home.donate');
    }

    public function donationComplete($id)
    {
        $donation = Donation::where('id', $id)->firstOrFail();
        return view('donation_complete', compact('donation'));
    }
}
