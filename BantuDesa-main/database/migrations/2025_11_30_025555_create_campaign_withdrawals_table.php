<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('campaign_withdrawals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('campaign_id')->constrained('campaigns')->onDelete('cascade');
            $table->decimal('amount', 15, 2); // Jumlah yang ditarik
            $table->string('recipient_name'); // Nama penerima (misal: Kepala Desa A)
            $table->string('bank_name')->nullable();
            $table->string('account_number')->nullable();
            $table->text('description'); // Untuk apa dana digunakan (Rincian)
            $table->string('proof_image')->nullable(); // Foto bukti transfer/nota
            $table->date('withdrawal_date');
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('campaign_withdrawals');
    }
};