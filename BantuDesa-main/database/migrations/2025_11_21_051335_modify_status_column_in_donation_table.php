<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Menggunakan Raw Statement untuk memaksa ubah kolom jadi VARCHAR(255)
        // Ini mengatasi masalah ENUM atau VARCHAR pendek
        DB::statement("ALTER TABLE donation MODIFY COLUMN status VARCHAR(255) DEFAULT 'pending'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Opsional: Kembalikan ke ENUM jika perlu (sesuaikan dengan kondisi awal Anda jika tahu)
        // DB::statement("ALTER TABLE donation MODIFY COLUMN status ENUM('pending', 'paid', 'failed') DEFAULT 'pending'");
    }
};