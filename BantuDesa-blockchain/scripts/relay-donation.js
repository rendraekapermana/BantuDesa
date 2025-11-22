// Lokasi: BantuDesa-blockchain/scripts/relay-donation.js
require('dotenv').config();
const hre = require("hardhat");

// [FIX] Tutup koneksi stdin agar tidak menunggu input dari PHP
if (process.stdin.setRawMode) {
    process.stdin.setRawMode(false);
}
process.stdin.resume();
process.stdin.end();

async function main() {
    // 1. Tangkap Data dari Environment Variable
    const donationId = process.env.DONATION_ID;
    const amountRupiah = process.env.AMOUNT_RUPIAH;
    const contractAddress = process.env.CONTRACT_ADDRESS;

    if (!donationId || !amountRupiah || !contractAddress) {
        console.error("Error: Data tidak lengkap.");
        process.exit(1);
    }

    // 2. Setup Signer
    const [deployer] = await hre.ethers.getSigners(); 
    
    // 3. Setup Contract Manual
    const artifact = await hre.artifacts.readArtifact("DonationTracker");
    const contract = new hre.ethers.Contract(contractAddress, artifact.abi, deployer);

    // 4. Konversi Data
    const amountConverted = BigInt(amountRupiah); 

    // 5. Kirim Transaksi
    try {
        const tx = await contract.recordDonation(donationId, amountConverted);
        await tx.wait(1);

        console.log(`SUCCESS_HASH:${tx.hash}`);
        console.log(`SUCCESS_ADDR:${deployer.address}`);
        
    } catch (error) {
        console.error("Transaction Error Details:", error);
        process.exit(1);
    }
}

main()
    .then(() => process.exit(0))
    .catch((error) => {
        console.error(error);
        process.exit(1);
    });