<?php

// database/migrations/..._add_blockchain_tx_hash_to_donations_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('donation', function (Blueprint $table) {
            // Kolom untuk menyimpan Tx Hash dari blockchain
            $table->string('blockchain_tx_hash', 66)->nullable()->after('status'); 
            // Kolom untuk menyimpan alamat wallet donatur
            $table->string('donor_wallet_address', 42)->nullable()->after('blockchain_tx_hash'); 
        });
    }

    public function down(): void
    {
        Schema::table('donation', function (Blueprint $table) {
            $table->dropColumn('blockchain_tx_hash');
            $table->dropColumn('donor_wallet_address');
        });
    }
};