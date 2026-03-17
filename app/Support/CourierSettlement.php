<?php

namespace App\Support;

use App\Models\Order;

class CourierSettlement
{
    public static function systemDeliveryCharge(Order $order): float
    {
        $systemCharge = (float) ($order->courier_charge ?? 0);

        if ($systemCharge <= 0) {
            $systemCharge = (float) ($order->delivery_fee ?? 0);
        }

        return round(max($systemCharge, 0), 2);
    }

    public static function defaultRealDeliveryCharge(Order $order): float
    {
        $storedRealCharge = (float) ($order->courier_cost ?? 0);

        if ($storedRealCharge > 0) {
            return round($storedRealCharge, 2);
        }

        return self::systemDeliveryCharge($order);
    }

    public static function realDeliveryCharge(Order $order, ?float $override = null): float
    {
        if ($override !== null) {
            return round(max($override, 0), 2);
        }

        return self::defaultRealDeliveryCharge($order);
    }

    public static function courierCommission(Order $order, ?float $overrideRealCharge = null): float
    {
        return round(
            self::systemDeliveryCharge($order) - self::realDeliveryCharge($order, $overrideRealCharge),
            2
        );
    }

    public static function receivedAmount(Order $order, ?float $overrideRealCharge = null): float
    {
        return round(
            (float) ($order->total_amount ?? 0) - self::realDeliveryCharge($order, $overrideRealCharge),
            2
        );
    }

    public static function serializeOrder(Order $order, ?float $overrideRealCharge = null): array
    {
        $realDeliveryCharge = self::realDeliveryCharge($order, $overrideRealCharge);

        return [
            'id' => $order->id,
            'waybill_number' => $order->waybill_number,
            'order_no' => $order->order_number ?: ('Order #' . $order->id),
            'payment_method' => (string) ($order->payment_method ?? '-'),
            'delivery_status' => (string) ($order->delivery_status ?? 'pending'),
            'order_amount' => round((float) ($order->total_amount ?? 0), 2),
            'system_delivery_charge' => self::systemDeliveryCharge($order),
            'real_delivery_charge' => $realDeliveryCharge,
            'courier_commission' => self::courierCommission($order, $realDeliveryCharge),
            'received_amount' => self::receivedAmount($order, $realDeliveryCharge),
        ];
    }
}
