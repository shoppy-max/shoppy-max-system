<?php

namespace App\Services;

use App\Models\CourierPayment;
use App\Models\Order;
use App\Models\OrderLog;

class CourierPaymentOrderService
{
    public function __construct(
        private readonly InventoryUnitService $inventoryUnits
    ) {
    }

    public function attachOrderToPayment(
        Order $order,
        CourierPayment $courierPayment,
        float $realCharge,
        ?int $userId = null
    ): void {
        $wasDelivered = strtolower((string) ($order->delivery_status ?? 'pending')) === 'delivered';

        $order->courier_cost = round(max($realCharge, 0), 2);
        $order->courier_payment_id = $courierPayment->id;
        $order->payment_status = 'paid';
        $order->delivery_status = 'delivered';

        if (!$order->delivered_at) {
            $order->delivered_at = now();
        }

        $order->save();

        $this->inventoryUnits->markOrderUnitsDelivered($order, $userId);

        $this->log(
            $order,
            $userId,
            'courier_payment_attached',
            $wasDelivered
                ? "Courier payment {$courierPayment->id} linked and settlement updated."
                : "Marked delivered through courier payment {$courierPayment->id}."
        );
    }

    public function detachOrderFromPayment(
        Order $order,
        ?int $userId = null,
        ?int $expectedCourierPaymentId = null
    ): void {
        if (
            $expectedCourierPaymentId !== null
            && (int) ($order->courier_payment_id ?? 0) !== $expectedCourierPaymentId
        ) {
            return;
        }

        $hadCourierPayment = (int) ($order->courier_payment_id ?? 0) > 0;

        $order->courier_payment_id = null;
        $order->courier_cost = 0;

        if (strtoupper((string) ($order->payment_method ?? '')) === 'COD') {
            $order->payment_status = 'pending';
        }

        $order->delivery_status = 'dispatched';
        $order->delivered_at = null;
        $order->save();

        $this->inventoryUnits->markOrderUnitsAllocated($order, $userId);

        if ($hadCourierPayment) {
            $paymentId = $expectedCourierPaymentId ?? 'previous';
            $this->log(
                $order,
                $userId,
                'courier_payment_detached',
                "Courier payment {$paymentId} was removed. Order returned to dispatched."
            );
        }
    }

    private function log(Order $order, ?int $userId, string $action, string $description): void
    {
        OrderLog::create([
            'order_id' => $order->id,
            'user_id' => $userId,
            'action' => $action,
            'description' => $description,
        ]);
    }
}
