<?php
// bantudesa-app/app/Http/Controllers/Web3Controller.php

namespace App\Http\Controllers;

use App\Models\Donation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class Web3Controller extends Controller
{
    public function recordSuccess(Request $request)
    {
        // 1. Validasi Data yang dikirim dari Frontend (JavaScript)
        $validated = $request->validate([
            'donation_id' => 'required|exists:donation,id', 
            'tx_hash' => 'required|string|max:66', 
            'wallet' => 'required|string|max:42' 
        ]);

        try {
            // 2. Cari Donasi dan pastikan belum diisi hash-nya
            $donation = Donation::where('id', $validated['donation_id'])->firstOrFail();

            // 3. Simpan Bukti Hash (MENGISI NILAI NULL)
            if ($donation->blockchain_tx_hash === null) {
                $donation->blockchain_tx_hash = $validated['tx_hash'];
                $donation->donor_wallet_address = $validated['wallet'];
                $donation->status = 'onchain_recorded'; // Donasi sudah tercatat di blockchain
                $donation->save();
            }

            // 4. Kirim respon sukses balik ke JavaScript
            return response()->json([
                'message' => 'Bukti Hash blockchain tersimpan.',
                'donation_id' => $donation->id,
                'hash' => $donation->blockchain_tx_hash
            ]);

        } catch (\Exception $e) {
            Log::error('Web3 Record Error: ' . $e->getMessage());
            return response()->json(['error' => 'Gagal memproses konfirmasi di sisi server.'], 500);
        }
    }
}