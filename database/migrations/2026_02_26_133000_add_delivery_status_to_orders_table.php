<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (!Schema::hasColumn('orders', 'delivery_status')) {
            Schema::table('orders', function (Blueprint $table) {
                $table->string('delivery_status')->default('pending')->after('status');
            });
        }

        DB::table('orders')
            ->where('status', 'cancel')
            ->update(['delivery_status' => 'cancel']);

        DB::table('orders')
            ->where('status', '!=', 'cancel')
            ->where(function ($query) {
                $query->whereNotNull('waybill_number')
                    ->where('waybill_number', '!=', '');
            })
            ->update(['delivery_status' => 'waybill_printed']);

        DB::table('orders')
            ->where(function ($query) {
                $query->whereNull('delivery_status')
                    ->orWhere('delivery_status', '');
            })
            ->update(['delivery_status' => 'pending']);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasColumn('orders', 'delivery_status')) {
            Schema::table('orders', function (Blueprint $table) {
                $table->dropColumn('delivery_status');
            });
        }
    }
};
