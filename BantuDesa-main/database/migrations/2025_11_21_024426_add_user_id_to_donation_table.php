<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('donation', function (Blueprint $table) {
            // HANYA tambahkan kolom yang belum ada, yaitu user_id
            // user_id harus ditambahkan untuk menghindari error pada DonationController
            
            // Kolom user_id
            $table->foreignId('user_id')->nullable()->constrained()->after('id');
            
            // Kolom blockchain_tx_hash dan donor_wallet_address diyakini sudah ada
            // di database, jadi kita HAPUS logic penambahannya di sini.
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('donation', function (Blueprint $table) {
            // Hapus foreign key constraint sebelum menghapus kolom
            $table->dropConstrainedForeignId('user_id'); 
            
            // KITA TIDAK MENAMBAHKAN DROP KOLOM BLOCKCHAIN DI SINI
            // Karena kita asumsikan kolom-kolom itu ditambahkan di migrasi lain yang sukses.
        });
    }
};