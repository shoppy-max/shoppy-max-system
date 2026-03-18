<?php

namespace App\Exports;

use App\Models\Order;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class OrdersExport implements FromCollection, WithHeadings, WithMapping, ShouldAutoSize, WithStyles
{
    public function __construct(private readonly Collection $orders)
    {
    }

    public function collection(): Collection
    {
        return $this->orders;
    }

    public function headings(): array
    {
        return [
            'Order Date',
            'Order ID',
            'Internal ID',
            'Waybill Number',
            'Order Type',
            'Call Status',
            'Delivery Status',
            'Payment Status',
            'Payment Method',
            'Customer Name',
            'Primary Mobile',
            'Secondary Mobile',
            'Address',
            'City',
            'District',
            'Province',
            'Reseller',
            'Courier',
            'Sub Total',
            'Delivery Charge',
            'Real Courier Charge',
            'Discount Type',
            'Discount Value',
            'Discount Amount',
            'Total Amount',
            'Paid Amount',
            'Balance',
            'Return Fee Applied',
            'Reseller Commission',
            'Item Count',
            'PCS Quantity',
            'Items Summary',
            'Sales Note',
            'Created By',
            'Waybill Printed At',
            'Picked At',
            'Packed At',
            'Dispatched At',
            'Cancelled At',
            'Delivered At',
            'Returned At',
            'Created At',
            'Updated At',
        ];
    }

    public function map($order): array
    {
        /** @var Order $order */
        $customer = $order->customer;
        $items = $order->items ?? collect();
        $itemCount = $items->count();
        $pcsQuantity = (int) $items->sum(fn ($item) => (int) ($item->quantity ?? 0));
        $subTotal = max(((float) $order->total_amount) - ((float) ($order->courier_charge ?? 0)) + ((float) ($order->discount_amount ?? 0)), 0);
        $returnFeeDeduction = ((string) ($order->order_type ?? '') === 'reseller'
            && strtolower((string) ($order->delivery_status ?? '')) === 'returned')
            ? (float) ($order->reseller_return_fee_applied ?? 0)
            : 0;
        $balance = max(((float) $order->total_amount) - ((float) ($order->paid_amount ?? 0)) - $returnFeeDeduction, 0);

        return [
            optional($order->order_date)->format('Y-m-d') ?? '',
            $order->order_number,
            $order->id,
            $order->waybill_number ?? '',
            ucfirst((string) ($order->order_type ?? '')),
            $this->label((string) ($order->call_status ?? '')),
            $this->deliveryLabel((string) ($order->delivery_status ?? 'pending')),
            $this->label((string) ($order->payment_status ?? 'pending')),
            (string) ($order->payment_method ?? ''),
            (string) ($customer->name ?? $order->customer_name ?? ''),
            (string) ($customer->mobile ?? $order->customer_phone ?? ''),
            (string) ($customer->landline ?? ''),
            (string) ($customer->address ?? $order->customer_address ?? ''),
            (string) ($order->customer_city ?? ''),
            (string) ($order->customer_district ?? ''),
            (string) ($order->customer_province ?? ''),
            $this->resellerLabel($order),
            (string) ($order->courier->name ?? ''),
            number_format($subTotal, 2, '.', ''),
            number_format((float) ($order->courier_charge ?? 0), 2, '.', ''),
            number_format((float) ($order->courier_cost ?? 0), 2, '.', ''),
            ucfirst((string) ($order->discount_type ?? 'fixed')),
            number_format((float) ($order->discount_value ?? 0), 2, '.', ''),
            number_format((float) ($order->discount_amount ?? 0), 2, '.', ''),
            number_format((float) ($order->total_amount ?? 0), 2, '.', ''),
            number_format((float) ($order->paid_amount ?? 0), 2, '.', ''),
            number_format($balance, 2, '.', ''),
            number_format($returnFeeDeduction, 2, '.', ''),
            number_format((float) ($order->total_commission ?? 0), 2, '.', ''),
            $itemCount,
            $pcsQuantity,
            $items->map(function ($item) {
                $name = trim((string) ($item->product_name ?? ''));
                $qty = (int) ($item->quantity ?? 0);

                return $name !== '' ? "{$name} x {$qty}" : null;
            })->filter()->implode(' | '),
            (string) ($order->sales_note ?? ''),
            (string) ($order->user->name ?? ''),
            optional($order->waybill_printed_at)->format('Y-m-d H:i:s') ?? '',
            optional($order->picked_at)->format('Y-m-d H:i:s') ?? '',
            optional($order->packed_at)->format('Y-m-d H:i:s') ?? '',
            optional($order->dispatched_at)->format('Y-m-d H:i:s') ?? '',
            optional($order->cancelled_at)->format('Y-m-d H:i:s') ?? '',
            optional($order->delivered_at)->format('Y-m-d H:i:s') ?? '',
            optional($order->returned_at)->format('Y-m-d H:i:s') ?? '',
            optional($order->created_at)->format('Y-m-d H:i:s') ?? '',
            optional($order->updated_at)->format('Y-m-d H:i:s') ?? '',
        ];
    }

    public function styles(Worksheet $sheet): array
    {
        return [
            1 => ['font' => ['bold' => true]],
        ];
    }

    private function label(string $value): string
    {
        $normalized = trim($value);
        if ($normalized === '') {
            return '';
        }

        return ucwords(str_replace('_', ' ', $normalized));
    }

    private function deliveryLabel(string $value): string
    {
        $labels = [
            'pending' => 'Pending',
            'waybill_printed' => 'Waybill Printed',
            'picked_from_rack' => 'Picked From Rack',
            'packed' => 'Packed',
            'dispatched' => 'Dispatched',
            'delivered' => 'Delivered',
            'returned' => 'Returned',
            'cancel' => 'Cancel',
        ];

        return $labels[$value] ?? $this->label($value);
    }

    private function resellerLabel(Order $order): string
    {
        $reseller = $order->reseller;
        if (!$reseller) {
            return '';
        }

        $business = trim((string) ($reseller->business_name ?? ''));
        $name = trim((string) ($reseller->name ?? ''));

        if ($business !== '' && $name !== '') {
            return "{$business} ({$name})";
        }

        return $business !== '' ? $business : $name;
    }
}
