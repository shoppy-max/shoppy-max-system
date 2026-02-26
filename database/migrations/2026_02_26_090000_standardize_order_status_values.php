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
        DB::statement(<<<'SQL'
            UPDATE orders
            SET status = CASE
                WHEN status IS NULL OR TRIM(status) = '' THEN 'pending'
                WHEN LOWER(status) = 'pending' THEN 'pending'
                WHEN LOWER(status) = 'hold' THEN 'hold'
                WHEN LOWER(status) = 'confirm' THEN 'confirm'
                WHEN LOWER(status) = 'cancel' THEN 'cancel'
                WHEN LOWER(status) = 'confirmed' THEN 'confirm'
                WHEN LOWER(status) = 'cancelled' THEN 'cancel'
                WHEN LOWER(status) = 'canceled' THEN 'cancel'
                WHEN LOWER(status) = 'on_hold' THEN 'hold'
                WHEN LOWER(status) = 'packing' THEN 'hold'
                WHEN LOWER(status) = 'processing' THEN 'hold'
                WHEN LOWER(status) = 'dispatched' THEN 'confirm'
                WHEN LOWER(status) = 'shipped' THEN 'confirm'
                WHEN LOWER(status) = 'delivered' THEN 'confirm'
                WHEN LOWER(status) = 'returned' THEN 'cancel'
                ELSE 'pending'
            END
        SQL);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // One-way normalization migration.
    }
};
