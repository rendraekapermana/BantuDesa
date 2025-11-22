import { HardhatUserConfig } from "hardhat/config";
import "@nomicfoundation/hardhat-toolbox";
import * as dotenv from "dotenv";

dotenv.config();

const config: HardhatUserConfig = {
  solidity: "0.8.24",
  networks: {
    // Konfigurasi Localhost (bawaan)
    localhost: {
      url: "http://127.0.0.1:8545",
    },
    // Konfigurasi Sepolia (Testnet)
    sepolia: {
      url: process.env.RPC_URL || "https://eth-sepolia.g.alchemy.com/v2/T5Q4Psml99Z_zSVcs-BoY", // Pastikan URL string tidak undefined
      accounts: process.env.PRIVATE_KEY ? [process.env.PRIVATE_KEY] : [],
    },
  },
};

export default config;