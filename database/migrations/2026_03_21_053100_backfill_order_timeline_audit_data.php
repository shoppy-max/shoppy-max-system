<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('orders')
            ->whereNotNull('waybill_number')
            ->where('waybill_number', '!=', '')
            ->whereNull('waybill_printed_at')
            ->update([
                'waybill_printed_at' => DB::raw('COALESCE(dispatched_at, delivered_at, returned_at, cancelled_at, created_at)'),
            ]);

        DB::table('orders')
            ->whereNotNull('waybill_printed_at')
            ->whereNull('waybill_printed_by')
            ->update([
                'waybill_printed_by' => DB::raw('COALESCE(user_id, packed_by)'),
            ]);

        DB::table('orders')
            ->whereIn('delivery_status', ['picked_from_rack', 'packed', 'dispatched', 'delivered', 'returned'])
            ->whereNull('picked_at')
            ->update([
                'picked_at' => DB::raw('COALESCE(packed_at, dispatched_at, delivered_at, returned_at, waybill_printed_at, created_at)'),
            ]);

        DB::table('orders')
            ->whereNotNull('picked_at')
            ->whereNull('picked_by')
            ->update([
                'picked_by' => DB::raw('COALESCE(packed_by, user_id)'),
            ]);

        DB::table('orders')
            ->whereIn('delivery_status', ['packed', 'dispatched', 'delivered', 'returned'])
            ->whereNull('packed_at')
            ->update([
                'packed_at' => DB::raw('COALESCE(dispatched_at, delivered_at, returned_at, picked_at, waybill_printed_at, created_at)'),
            ]);

        DB::table('orders')
            ->whereNotNull('packed_at')
            ->whereNull('packed_by')
            ->update([
                'packed_by' => DB::raw('COALESCE(picked_by, user_id)'),
            ]);

        DB::table('orders')
            ->whereNotNull('dispatched_at')
            ->whereNull('dispatched_by')
            ->update([
                'dispatched_by' => DB::raw('COALESCE(packed_by, picked_by, user_id)'),
            ]);

        DB::table('orders')
            ->whereNotNull('cancelled_at')
            ->whereNull('cancelled_by')
            ->update([
                'cancelled_by' => DB::raw('COALESCE(user_id, packed_by)'),
            ]);

        DB::table('orders')
            ->whereNotNull('delivered_at')
            ->whereNull('delivered_by')
            ->update([
                'delivered_by' => DB::raw('COALESCE(dispatched_by, packed_by, user_id)'),
            ]);

        DB::table('orders')
            ->whereNotNull('returned_at')
            ->whereNull('returned_by')
            ->update([
                'returned_by' => DB::raw('COALESCE(delivered_by, dispatched_by, packed_by, user_id)'),
            ]);
    }

    public function down(): void
    {
    }
};
