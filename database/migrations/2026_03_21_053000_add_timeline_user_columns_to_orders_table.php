<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            if (!Schema::hasColumn('orders', 'waybill_printed_by')) {
                $table->foreignId('waybill_printed_by')->nullable()->after('waybill_printed_at')->constrained('users')->nullOnDelete();
            }

            if (!Schema::hasColumn('orders', 'picked_by')) {
                $table->foreignId('picked_by')->nullable()->after('picked_at')->constrained('users')->nullOnDelete();
            }

            if (!Schema::hasColumn('orders', 'dispatched_by')) {
                $table->foreignId('dispatched_by')->nullable()->after('dispatched_at')->constrained('users')->nullOnDelete();
            }

            if (!Schema::hasColumn('orders', 'cancelled_by')) {
                $table->foreignId('cancelled_by')->nullable()->after('cancelled_at')->constrained('users')->nullOnDelete();
            }

            if (!Schema::hasColumn('orders', 'delivered_by')) {
                $table->foreignId('delivered_by')->nullable()->after('delivered_at')->constrained('users')->nullOnDelete();
            }

            if (!Schema::hasColumn('orders', 'returned_by')) {
                $table->foreignId('returned_by')->nullable()->after('returned_at')->constrained('users')->nullOnDelete();
            }
        });

        DB::table('orders')
            ->whereNotNull('waybill_printed_at')
            ->whereNull('waybill_printed_by')
            ->update([
                'waybill_printed_by' => DB::raw('COALESCE(user_id, packed_by)'),
            ]);

        DB::table('orders')
            ->whereNotNull('picked_at')
            ->whereNull('picked_by')
            ->update([
                'picked_by' => DB::raw('COALESCE(packed_by, user_id)'),
            ]);

        DB::table('orders')
            ->whereNotNull('dispatched_at')
            ->whereNull('dispatched_by')
            ->update([
                'dispatched_by' => DB::raw('COALESCE(packed_by, user_id)'),
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
                'delivered_by' => DB::raw('COALESCE(user_id, packed_by)'),
            ]);

        DB::table('orders')
            ->whereNotNull('returned_at')
            ->whereNull('returned_by')
            ->update([
                'returned_by' => DB::raw('COALESCE(user_id, packed_by)'),
            ]);
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            foreach (['waybill_printed_by', 'picked_by', 'dispatched_by', 'cancelled_by', 'delivered_by', 'returned_by'] as $column) {
                if (Schema::hasColumn('orders', $column)) {
                    $table->dropForeign([$column]);
                    $table->dropColumn($column);
                }
            }
        });
    }
};
