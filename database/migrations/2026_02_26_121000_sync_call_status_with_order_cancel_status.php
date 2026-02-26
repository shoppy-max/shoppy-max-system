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
        DB::table('orders')
            ->where('status', 'cancel')
            ->update(['call_status' => 'cancel']);

        DB::table('orders')
            ->where('status', '!=', 'cancel')
            ->where('call_status', 'cancel')
            ->update(['call_status' => 'pending']);

        DB::table('orders')
            ->where(function ($query) {
                $query->whereNull('call_status')
                    ->orWhereNotIn('call_status', ['pending', 'confirm', 'hold', 'cancel']);
            })
            ->update(['call_status' => 'pending']);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // One-way data synchronization migration.
    }
};
