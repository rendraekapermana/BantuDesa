const hre = require("hardhat");

async function main() {
  // 1. Ambil pabrik kontrak (Contract Factory)
  const DonationTracker = await hre.ethers.getContractFactory("DonationTracker");

  // 2. Mulai proses deployment
  console.log("Sedang men-deploy kontrak, mohon tunggu...");
  const donationTracker = await DonationTracker.deploy();

  // 3. Tunggu sampai selesai tercatat di blockchain
  await donationTracker.waitForDeployment();

  // 4. Tampilkan alamat kontrak
  console.log("Berhasil! DonationTracker deployed ke:", await donationTracker.getAddress());
}

main().catch((error) => {
  console.error(error);
  process.exitCode = 1;
});