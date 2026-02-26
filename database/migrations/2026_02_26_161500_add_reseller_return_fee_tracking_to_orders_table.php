<?php

use App\Models\Reseller;
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
        if (!Schema::hasColumn('orders', 'reseller_return_fee_applied')) {
            Schema::table('orders', function (Blueprint $table) {
                $table->decimal('reseller_return_fee_applied', 10, 2)->default(0)->after('delivery_status');
            });
        }

        if (!Schema::hasColumn('orders', 'return_fee_reseller_id')) {
            Schema::table('orders', function (Blueprint $table) {
                $table->unsignedBigInteger('return_fee_reseller_id')->nullable()->after('reseller_return_fee_applied');
            });
        }

        // Direct resellers do not use return fees.
        DB::table('resellers')
            ->where('reseller_type', Reseller::TYPE_DIRECT_RESELLER)
            ->update(['return_fee' => 0]);

        // Backfill already returned reseller orders once.
        $resellerFees = DB::table('resellers')
            ->where('reseller_type', Reseller::TYPE_RESELLER)
            ->pluck('return_fee', 'id');

        DB::table('orders')
            ->select('id', 'reseller_id')
            ->where('order_type', 'reseller')
            ->where('status', '!=', 'cancel')
            ->where('delivery_status', 'returned')
            ->whereNotNull('reseller_id')
            ->where(function ($query) {
                $query->whereNull('reseller_return_fee_applied')
                    ->orWhere('reseller_return_fee_applied', 0);
            })
            ->orderBy('id')
            ->chunkById(200, function ($orders) use ($resellerFees) {
                foreach ($orders as $order) {
                    $resellerId = (int) $order->reseller_id;
                    $fee = round(max((float) ($resellerFees[$resellerId] ?? 0), 0), 2);

                    if ($fee <= 0) {
                        continue;
                    }

                    DB::table('orders')
                        ->where('id', $order->id)
                        ->update([
                            'reseller_return_fee_applied' => $fee,
                            'return_fee_reseller_id' => $resellerId,
                        ]);

                    DB::table('resellers')
                        ->where('id', $resellerId)
                        ->decrement('due_amount', $fee);
                }
            });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasColumn('orders', 'return_fee_reseller_id')) {
            Schema::table('orders', function (Blueprint $table) {
                $table->dropColumn('return_fee_reseller_id');
            });
        }

        if (Schema::hasColumn('orders', 'reseller_return_fee_applied')) {
            Schema::table('orders', function (Blueprint $table) {
                $table->dropColumn('reseller_return_fee_applied');
            });
        }
    }
};

