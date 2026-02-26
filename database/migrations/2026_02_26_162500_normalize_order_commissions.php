<?php

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Reseller;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Order::query()
            ->with([
                'items:id,order_id,quantity,unit_price,base_price',
                'reseller:id,reseller_type',
            ])
            ->select(['id', 'order_type', 'status', 'delivery_status', 'reseller_id', 'discount_amount', 'total_commission'])
            ->orderBy('id')
            ->chunkById(200, function ($orders) {
                foreach ($orders as $order) {
                    $isEligible = (string) ($order->order_type ?? '') === 'reseller'
                        && (string) ($order->status ?? '') !== 'cancel'
                        && strtolower((string) ($order->delivery_status ?? '')) !== 'returned'
                        && $order->reseller
                        && $order->reseller->reseller_type === Reseller::TYPE_RESELLER;

                    $commission = 0.0;
                    if ($isEligible) {
                        $grossCommission = (float) $order->items->sum(function (OrderItem $item) {
                            $qty = max((int) ($item->quantity ?? 0), 0);
                            $marginPerItem = (float) ($item->unit_price ?? 0) - (float) ($item->base_price ?? 0);

                            return max($marginPerItem, 0) * $qty;
                        });

                        $commission = max($grossCommission - (float) ($order->discount_amount ?? 0), 0);
                    }

                    $normalized = round($commission, 2);
                    if ((float) ($order->total_commission ?? 0) !== $normalized) {
                        $order->total_commission = $normalized;
                        $order->save();
                    }
                }
            });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // No-op: normalization is a forward data correction.
    }
};

