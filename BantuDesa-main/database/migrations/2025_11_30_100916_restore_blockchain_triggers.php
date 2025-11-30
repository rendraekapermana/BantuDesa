<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // --- Pastikan Trigger Lama Dihapus Lebih Dulu ---
        DB::unprepared('DROP TRIGGER IF EXISTS prevent_update_on_finalized_donation');
        DB::unprepared('DROP TRIGGER IF EXISTS prevent_delete_on_finalized_donation');

        // --- 1. Trigger untuk mencegah UPDATE data final ---
        DB::unprepared("
            CREATE TRIGGER prevent_update_on_finalized_donation
            BEFORE UPDATE ON donation
            FOR EACH ROW
            BEGIN
                IF OLD.blockchain_tx_hash IS NOT NULL AND OLD.blockchain_tx_hash != '' THEN
                    
                    IF NEW.amount != OLD.amount THEN
                        SIGNAL SQLSTATE '45000'
                        SET MESSAGE_TEXT = 'SECURITY ALERT: Tidak dapat mengubah nominal donasi yang sudah tercatat di Blockchain!';
                    END IF;

                    IF NEW.status != OLD.status AND OLD.status = 'recorded_on_chain' THEN
                        SIGNAL SQLSTATE '45000'
                        SET MESSAGE_TEXT = 'SECURITY ALERT: Tidak dapat mengubah status donasi yang sudah final di Blockchain!';
                    END IF;

                    IF NEW.blockchain_tx_hash != OLD.blockchain_tx_hash THEN
                        SIGNAL SQLSTATE '45000'
                        SET MESSAGE_TEXT = 'SECURITY ALERT: Dilarang mengubah/menghapus bukti Hash Blockchain!';
                    END IF;

                END IF;
            END
        ");

        // --- 2. Trigger untuk Mencegah DELETE data final ---
        DB::unprepared("
            CREATE TRIGGER prevent_delete_on_finalized_donation
            BEFORE DELETE ON donation
            FOR EACH ROW
            BEGIN
                IF OLD.blockchain_tx_hash IS NOT NULL AND OLD.blockchain_tx_hash != '' THEN
                    SIGNAL SQLSTATE '45000'
                    SET MESSAGE_TEXT = 'SECURITY ALERT: Data ini terikat dengan Blockchain dan bersifat abadi (Immutable). Tidak dapat dihapus!';
                END IF;
            END
        ");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::unprepared('DROP TRIGGER IF EXISTS prevent_update_on_finalized_donation');
        DB::unprepared('DROP TRIGGER IF EXISTS prevent_delete_on_finalized_donation');
    }
};
