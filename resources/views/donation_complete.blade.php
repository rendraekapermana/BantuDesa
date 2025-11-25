@extends('layouts.app')

@section('content')

<div class="container py-5 text-center">
{{-- Ikon sukses besar --}}
<i class="fas fa-check-circle text-success" style="font-size: 50px;"></i>

<h2 class="mt-3 text-success">🎉 Donasi Berhasil Dicatat Permanen!</h2>
<p class="lead">Terima kasih. Bukti transaksi Anda telah tersimpan di jaringan Sepolia.</p>

<div class="card p-4 mx-auto" style="max-width: 600px; text-align: left;">
    <h4>Detail Donasi #{{ $donation->id }}:</h4>
    <p><strong>Status Final:</strong> <span class="badge bg-success">{{ strtoupper($donation->status) }}</span></p>
    <p><strong>Jumlah Donasi:</strong> Rp. {{ number_format($donation->amount, 0, ',', '.') }}</p>
    <p><strong>Alamat Wallet:</strong> <code>{{ $donation->donor_wallet_address ?? 'Belum tercatat.' }}</code></p>
    <hr>
    
    <p class="fw-bold mb-1">Bukti Kekekalan (Transaction Hash):</p>
    @if($donation->blockchain_tx_hash)
        <a href="https://sepolia.etherscan.io/tx/{{ $donation->blockchain_tx_hash }}" target="_blank" class="text-break">
            <code>{{ $donation->blockchain_tx_hash }}</code>
        </a>
        <p class="mt-2"><small class="text-muted">Klik link di atas untuk memverifikasi di Etherscan.</small></p>
    @else
        <span class="text-danger">HASH BELUM TERCATAT LENGKAP (Coba muat ulang halaman atau hubungi admin).</span>
    @endif
</div>

<a href="{{ route('home.index') }}" class="btn btn-primary mt-4">Kembali ke Beranda</a>


</div>
@endsection