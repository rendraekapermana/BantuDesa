<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('campaigns', function (Blueprint $table) {
            $table->id();
            $table->string('title'); // Nama Desa / Proyek
            $table->text('description')->nullable();
            $table->decimal('target_amount', 15, 2)->default(0); // Target donasi
            $table->decimal('current_amount', 15, 2)->default(0); // Terkumpul
            $table->string('image')->nullable(); // Foto desa
            $table->enum('status', ['active', 'completed', 'inactive'])->default('active');
            $table->timestamps();
        });

        // Tambahkan kolom campaign_id ke tabel donation
        Schema::table('donation', function (Blueprint $table) {
            $table->foreignId('campaign_id')->nullable()->constrained('campaigns')->onDelete('set null');
        });
    }

    public function down()
    {
        Schema::table('donation', function (Blueprint $table) {
            $table->dropForeign(['campaign_id']);
            $table->dropColumn('campaign_id');
        });
        Schema::dropIfExists('campaigns');
    }
};