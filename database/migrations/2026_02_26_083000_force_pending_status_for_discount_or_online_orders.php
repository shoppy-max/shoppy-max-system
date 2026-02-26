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
            ->where(function ($query) {
                $query->where('discount_amount', '>', 0)
                    ->orWhere('payment_method', 'Online Payment');
            })
            ->update(['status' => 'pending']);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // One-way data normalization.
    }
};
