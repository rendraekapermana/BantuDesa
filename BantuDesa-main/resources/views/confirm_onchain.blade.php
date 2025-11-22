@extends('layouts.app') 

@section('content')
<div class="container py-5">
    <h2>Konfirmasi Pencatatan Blockchain</h2>
    <p class="lead">Langkah terakhir: Konfirmasikan donasi ini ke jaringan Sepolia. Ini akan menjamin nilai donasi Anda tidak dapat diubah (immutable).</p>

    <div class="card p-4 shadow-sm">
        <h4>Detail Donasi:</h4>
        <p><strong>ID Donasi Lokal:</strong> {{ $donation->id }}</p>
        <p><strong>Jumlah:</strong> Rp. {{ number_format($donation->amount, 0, ',', '.') }}</p>
        <hr>

        <p class="text-info">Pastikan MetaMask Anda terhubung ke jaringan **Sepolia Testnet** dan memiliki saldo Testnet ETH/MATIC yang cukup untuk biaya Gas (sangat kecil).</p>

        <div id="statusMessage" class="alert alert-warning">Menunggu koneksi MetaMask...</div>
        
        <button id="recordButton" class="btn btn-success btn-lg" onclick="recordDonationOnChain()" disabled>
            <i class="fas fa-link"></i> Catat Permanen di Blockchain
        </button>
    </div>
    
    <div id="txInfo" class="mt-4" style="display:none;">
        <h4>Bukti Transaksi Blockchain:</h4>
        <p><strong>Wallet Pengirim:</strong> <code id="walletAddressDisplay"></code></p>
        <p><strong>Transaction Hash:</strong> <code id="txHashDisplay"></code></p>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/ethers@6.11.1/dist/ethers.umd.min.js"></script>

<script>
    // 2. Ambil data penting dari PHP/Laravel
    const LARAVEL_ID = {{ $donation->id }};
    // Konversi jumlah Rupiah (misal Rp 10.000) menjadi mata uang yang akan dicatat di kontrak
    // Kontrak DonationTracker menerima uint256 amount (amountInUSD). 
    // Kita asumsikan 100 Rupiah = 1 unit kontrak untuk simplifikasi. (Anda harus konversi sesuai rate)
    const AMOUNT_FOR_CONTRACT = BigInt(Math.floor({{ $donation->amount }} / 100)); // Contoh: Rp 10.000 -> 100 units
    const CONTRACT_ADDRESS = '{{ $contractAddress }}'; // Alamat Anda: 0xD839A554FfA6f72cdA842712e1fa6c434f8AAEBE
    const ABI_JSON_STRING = '@json($abi)';
    const API_URL = '{{ route('web3.recordSuccess') }}'; 
    const CSRF_TOKEN = '{{ csrf_token() }}';

    // 3. Logic Utama Ethers.js
    async function recordDonationOnChain() {
        const statusDiv = document.getElementById('statusMessage');
        const recordButton = document.getElementById('recordButton');
        recordButton.disabled = true;
        statusDiv.className = 'alert alert-info';
        statusDiv.innerHTML = 'Meminta tanda tangan (signature) di MetaMask...';

        try {
            if (!window.ethereum) {
                statusDiv.className = 'alert alert-danger';
                statusDiv.innerHTML = 'MetaMask tidak ditemukan. Silakan instal atau buka di Browser yang mendukung.';
                return;
            }

            // Inisialisasi Provider dan Signer (yang memiliki private key user)
            const provider = new ethers.BrowserProvider(window.ethereum);
            const signer = await provider.getSigner();
            const walletAddress = await signer.getAddress();
            
            // Dapatkan instance Smart Contract
            const contract = new ethers.Contract(CONTRACT_ADDRESS, CONTRACT_ABI, signer);
            
            statusDiv.innerHTML = `Wallet terhubung: ${walletAddress}. Mengirim transaksi ke Sepolia... (Mohon konfirmasi di MetaMask)`;

            // Panggil fungsi recordDonation(uint256 _laravelId, uint256 _amount)
            const tx = await contract.recordDonation(
                LARAVEL_ID, 
                AMOUNT_FOR_CONTRACT
            );

            // Tunggu hingga transaksi dikonfirmasi di blockchain (terkunci di blok)
            statusDiv.className = 'alert alert-warning';
            statusDiv.innerHTML = `Transaksi dikirim! Menunggu konfirmasi... Hash: <a href="https://sepolia.etherscan.io/tx/${tx.hash}" target="_blank">${tx.hash.substring(0, 10)}...</a>`;

            const receipt = await tx.wait(); // Menunggu konfirmasi block
            const txHash = receipt.hash;

            // 4. Kirim Bukti (Transaction Hash) ke Backend Laravel
            const response = await fetch(API_URL, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': CSRF_TOKEN 
                },
                body: JSON.stringify({
                    donation_id: LARAVEL_ID,
                    tx_hash: txHash,
                    wallet: walletAddress
                })
            });

            if (response.ok) {
                statusDiv.className = 'alert alert-success';
                statusDiv.innerHTML = '✅ SUKSES! Donasi dicatat permanen di Blockchain dan Database Anda.';
                
                // Tampilkan bukti
                document.getElementById('walletAddressDisplay').innerText = walletAddress;
                document.getElementById('txHashDisplay').innerHTML = `<a href="https://sepolia.etherscan.io/tx/${txHash}" target="_blank">${txHash}</a>`;
                document.getElementById('txInfo').style.display = 'block';

            } else {
                statusDiv.className = 'alert alert-danger';
                statusDiv.innerHTML = 'Transaksi Blockchain sukses, tapi GAGAL mencatat bukti di Backend Laravel.';
            }

        } catch (error) {
            statusDiv.className = 'alert alert-danger';
            if (error.code === 4001) {
                statusDiv.innerHTML = "❌ Transaksi dibatalkan oleh Pengguna (MetaMask ditolak).";
            } else {
                statusDiv.innerHTML = "❌ Terjadi Kesalahan Blockchain. Pastikan Anda di Sepolia dan punya Testnet ETH.";
                console.error(error);
            }
            recordButton.disabled = false;
        }
    }
    
    // Inisialisasi ABI di Global Scope
    const CONTRACT_ABI = JSON.parse(ABI_JSON_STRING);

    // Aktifkan tombol jika Metamask/Ethereum terdeteksi
    if (window.ethereum) {
        document.getElementById('statusMessage').innerHTML = 'Klik tombol di bawah untuk memicu konfirmasi MetaMask.';
        document.getElementById('recordButton').disabled = false;
    }
</script>
@endsection