// SPDX-License-Identifier: MIT
pragma solidity ^0.8.20; // Menggunakan versi Solidity 0.8.20 atau yang lebih baru

contract DonationTracker {
    // Definisi struktur data untuk Donasi
    struct DonationRecord {
        uint256 laravelId;        // ID Donasi dari database Laravel
        uint256 amountInUSD;      // Jumlah donasi (gunakan satuan terkecil, misalnya cent, atau desimal)
        address donorWallet; // Alamat wallet yang mencatat donasi
        uint256 timestamp;
    }

    // Array (List) untuk menyimpan semua catatan donasi
    DonationRecord[] public donationRecords;

    // Event untuk mempermudah Backend Laravel (PHP) membaca konfirmasi
    event DonationRecorded(uint256 indexed laravelId, uint256 amountInUSD, address donorWallet, uint256 timestamp);

    // Fungsi yang dipanggil untuk mencatat donasi baru
    // Kita anggap amount yang dikirim sudah dalam USD (misalnya 10000 = $100.00)
    function recordDonation(uint256 _laravelId, uint256 _amount) public {
        // Cek bahwa ID Donasi dari Laravel belum pernah dicatat
        // (Untuk Smart Contract sederhana ini, kita lewatkan validasi ini dulu agar cepat)
        
        // Buat catatan baru
        DonationRecord memory newRecord = DonationRecord({
            laravelId: _laravelId,
            amountInUSD: _amount,
            donorWallet: msg.sender, // Alamat yang memanggil fungsi ini
            timestamp: block.timestamp
        });

        donationRecords.push(newRecord);

        // Keluarkan event agar Laravel tahu transaksi sukses
        emit DonationRecorded(_laravelId, _amount, msg.sender, block.timestamp);
    }

    // Fungsi untuk mengambil detail donasi (hanya membaca, tidak bayar gas)
    function getDonationRecord(uint256 _index) public view returns (uint256, uint256, address, uint256) {
        DonationRecord storage record = donationRecords[_index];
        return (record.laravelId, record.amountInUSD, record.donorWallet, record.timestamp);
    }
}