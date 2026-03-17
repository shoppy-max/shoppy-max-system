<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Invoice {{ $order->order_number }}</title>
    <style>
        @page {
            margin: 14mm;
        }

        body {
            margin: 0;
            font-family: DejaVu Sans, Helvetica, Arial, sans-serif;
            font-size: 12px;
            color: #1f2937;
            line-height: 1.45;
        }

        .paper {
            width: 100%;
        }

        .header {
            width: 100%;
            border-bottom: 1px solid #e5e7eb;
            padding-bottom: 14px;
            margin-bottom: 16px;
        }

        .header td {
            vertical-align: top;
        }

        .title {
            font-size: 24px;
            font-weight: 700;
            margin-bottom: 4px;
        }

        .meta {
            color: #4b5563;
            font-size: 11px;
        }

        .badge {
            display: inline-block;
            margin-top: 8px;
            border-radius: 999px;
            padding: 2px 8px;
            font-size: 10px;
            font-weight: 700;
            text-transform: uppercase;
        }

        .badge-pending { background: #fef3c7; color: #92400e; }
        .badge-confirm { background: #dbeafe; color: #1e40af; }
        .badge-hold { background: #ffedd5; color: #9a3412; }
        .badge-cancelled { background: #fee2e2; color: #991b1b; }
        .badge-other { background: #e5e7eb; color: #374151; }

        .company {
            text-align: right;
            font-size: 11px;
            color: #4b5563;
        }

        .company-name {
            font-size: 17px;
            font-weight: 700;
            color: #111827;
            margin-bottom: 4px;
        }

        .section-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 14px;
        }

        .section-table td {
            vertical-align: top;
            width: 50%;
            padding-right: 10px;
        }

        .label {
            margin-bottom: 6px;
            font-size: 10px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            color: #6b7280;
        }

        .value {
            font-size: 12px;
            color: #374151;
        }

        .value strong {
            color: #111827;
            font-size: 13px;
        }

        .items {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 14px;
        }

        .items th,
        .items td {
            border-bottom: 1px solid #eef2f7;
            padding: 8px;
        }

        .items th {
            background: #f9fafb;
            color: #6b7280;
            font-size: 10px;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            text-align: left;
        }

        .text-right { text-align: right; }
        .text-center { text-align: center; }

        .totals-wrap {
            width: 100%;
            margin-top: 4px;
        }

        .totals-wrap td {
            vertical-align: top;
        }

        .totals {
            width: 100%;
            border-collapse: collapse;
        }

        .totals td {
            padding: 5px 0;
            font-size: 12px;
            color: #374151;
        }

        .totals .grand td {
            border-top: 1px solid #d1d5db;
            padding-top: 8px;
            font-size: 15px;
            font-weight: 700;
            color: #111827;
        }

        .note {
            margin-top: 12px;
        }

        .note-box {
            margin-top: 5px;
            border: 1px solid #fef3c7;
            background: #fffbeb;
            padding: 8px;
            border-radius: 6px;
            font-size: 11px;
            color: #374151;
        }

        .footer {
            margin-top: 18px;
            border-top: 1px solid #e5e7eb;
            padding-top: 10px;
            font-size: 10px;
            color: #6b7280;
        }
        .muted-line {
            display: block;
            margin-top: 2px;
            font-size: 10px;
            color: #6b7280;
        }
        .tracking-list {
            margin-top: 6px;
            font-size: 9px;
            line-height: 1.45;
            color: #4b5563;
        }
        .tracking-unit {
            display: inline-block;
            margin: 0 3px 3px 0;
            padding: 2px 5px;
            border: 1px solid #d1d5db;
            border-radius: 10px;
            background: #f9fafb;
            font-family: monospace;
            font-size: 9px;
            color: #111827;
        }
    </style>
</head>
<body>
    @php
        $statusClass = match ($order->status) {
            'pending' => 'badge-pending',
            'confirm' => 'badge-confirm',
            'hold' => 'badge-hold',
            'cancel' => 'badge-cancelled',
            default => 'badge-other',
        };
        $deliveryStatus = strtolower((string) ($order->delivery_status ?? 'pending'));
        $deliveryLabels = [
            'pending' => 'Pending',
            'waybill_printed' => 'Waybill printed',
            'picked_from_rack' => 'Picked from rack',
            'packed' => 'Packed',
            'dispatched' => 'Dispatched',
            'delivered' => 'Delivered',
            'return_requested' => 'Return Requested',
            'returned' => 'Returned',
            'cancel' => 'Cancel',
        ];
    @endphp

    <div class="paper">
        <table class="header">
            <tr>
                <td>
                    <div class="title">Invoice</div>
                    <div class="meta">Order #{{ $order->order_number }}</div>
                    <div class="meta">Date: {{ optional($order->order_date)->format('d M, Y') }}</div>
                    <span class="badge {{ $statusClass }}">{{ $order->status }}</span>
                </td>
                <td class="company">
                    <div class="company-name">{{ config('app.name', 'ShoppyMax') }}</div>
                </td>
            </tr>
        </table>

        <table class="section-table">
            <tr>
                <td>
                    <div class="label">Invoice To</div>
                    <div class="value">
                        <strong>{{ $order->customer->name ?? $order->customer_name }}</strong><br>
                        {{ $order->customer->address ?? $order->customer_address }}<br>
                        {{ $order->customer->mobile ?? $order->customer_phone }}<br>
                        {{ $order->customer_city ?? $order->customer->city }}{{ $order->customer_district ? ', ' . $order->customer_district : '' }}{{ $order->customer_province ? ', ' . $order->customer_province : '' }}
                    </div>
                </td>
                <td>
                    @if($order->order_type === 'reseller' && $order->reseller)
                        <div class="label">Reseller Info</div>
                        <div class="value">
                            <strong>{{ $order->reseller->business_name ?: $order->reseller->name }}</strong><br>
                            Contact: {{ $order->reseller->name }}<br>
                            Mobile: {{ $order->reseller->mobile }}<br>
                            Account: {{ $order->reseller->reseller_type === 'direct_reseller' ? 'Direct Reseller' : 'Reseller' }}<br>
                            Delivery Status: {{ $deliveryLabels[$deliveryStatus] ?? 'Pending' }}
                        </div>
                    @else
                        <div class="label">Order Info</div>
                        <div class="value">
                            Type: {{ ucfirst($order->order_type) }}<br>
                            Payment: {{ $order->payment_method === 'COD' ? 'Cash on Delivery (COD)' : $order->payment_method }}<br>
                            @if($order->courier)Courier: {{ $order->courier->name }}<br>@endif
                            Call Status: {{ ucfirst((string) $order->call_status) }}<br>
                            Delivery Status: {{ $deliveryLabels[$deliveryStatus] ?? 'Pending' }}<br>
                            Created By: {{ $order->user->name ?? 'System' }}
                        </div>
                    @endif
                </td>
            </tr>
        </table>

        <table class="items">
            <thead>
                <tr>
                    <th>Product</th>
                    <th class="text-center">SKU</th>
                    <th class="text-center">Qty</th>
                    <th class="text-right">Unit Price</th>
                    <th class="text-right">Total</th>
                </tr>
            </thead>
            <tbody>
                @foreach($order->items as $item)
                    <tr>
                        <td>
                            {{ $item->product_name }}
                            <span class="muted-line">
                                Variant:
                                @if($item->variant)
                                    {{ $item->variant->unit_value ? $item->variant->unit_value . ' ' : '' }}{{ $item->variant->unit->name ?? ($item->variant->unit->short_name ?? '-') }}
                                @else
                                    -
                                @endif
                            </span>
                            @if($item->inventoryUnits->isNotEmpty())
                                <div class="tracking-list">
                                    <strong>Tracked Units:</strong><br>
                                    @foreach($item->inventoryUnits as $trackedUnit)
                                        <span class="tracking-unit">
                                            {{ $trackedUnit->unit_code }}{{ $trackedUnit->purchase?->purchase_number ? ' [' . $trackedUnit->purchase->purchase_number . ']' : ' [Legacy]' }}
                                        </span>
                                    @endforeach
                                </div>
                            @endif
                        </td>
                        <td class="text-center">{{ $item->sku }}</td>
                        <td class="text-center">{{ $item->quantity }}</td>
                        <td class="text-right">{{ number_format($item->unit_price, 2) }}</td>
                        <td class="text-right">{{ number_format($item->subtotal, 2) }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        <table class="totals-wrap">
            @php
                $paidAmount = (float) ($order->paid_amount ?? 0);
                $returnFeeDeduction = ((string) ($order->order_type ?? '') === 'reseller'
                    && strtolower((string) ($order->delivery_status ?? '')) === 'returned')
                    ? (float) ($order->reseller_return_fee_applied ?? 0)
                    : 0;
                $remainingAmount = max((float) $order->total_amount - $paidAmount - $returnFeeDeduction, 0);
                $discountAmount = (float) ($order->discount_amount ?? 0);
                $subTotalBeforeDiscount = max(((float) $order->total_amount - (float) $order->courier_charge) + $discountAmount, 0);
            @endphp
            <tr>
                <td style="width: 58%"></td>
                <td style="width: 42%">
                    <table class="totals">
                        @if($order->order_type === 'reseller')
                            <tr>
                                <td>Total Commission</td>
                                <td class="text-right">LKR {{ number_format($order->total_commission, 2) }}</td>
                            </tr>
                        @endif
                        <tr>
                            <td>Subtotal</td>
                            <td class="text-right">LKR {{ number_format($subTotalBeforeDiscount, 2) }}</td>
                        </tr>
                        <tr>
                            <td>Discount</td>
                            <td class="text-right">- LKR {{ number_format($discountAmount, 2) }}</td>
                        </tr>
                        <tr>
                            <td>Courier Charge</td>
                            <td class="text-right">LKR {{ number_format($order->courier_charge, 2) }}</td>
                        </tr>
                        <tr>
                            <td>Paid Amount</td>
                            <td class="text-right">LKR {{ number_format($paidAmount, 2) }}</td>
                        </tr>
                        @if($returnFeeDeduction > 0)
                            <tr>
                                <td>Return Fee Penalty</td>
                                <td class="text-right">- LKR {{ number_format($returnFeeDeduction, 2) }}</td>
                            </tr>
                        @endif
                        <tr>
                            <td>{{ $order->payment_method === 'COD' ? 'Remaining (COD Collect)' : 'Remaining Amount' }}</td>
                            <td class="text-right">LKR {{ number_format($remainingAmount, 2) }}</td>
                        </tr>
                        <tr class="grand">
                            <td>Grand Total</td>
                            <td class="text-right">LKR {{ number_format($order->total_amount, 2) }}</td>
                        </tr>
                    </table>
                </td>
            </tr>
        </table>

        @if(is_array($order->payments_data) && count($order->payments_data) > 0)
            <div class="note">
                <div class="label">Payment Entries</div>
                <table class="items">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th class="text-right">Amount</th>
                            <th>Note</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($order->payments_data as $payment)
                            <tr>
                                <td>{{ $payment['date'] ?? '-' }}</td>
                                <td class="text-right">LKR {{ number_format((float) ($payment['amount'] ?? 0), 2) }}</td>
                                <td>{{ $payment['note'] ?? '-' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif

        @if($order->sales_note)
            <div class="note">
                <div class="label">Sales Note / Remarks</div>
                <div class="note-box">{{ $order->sales_note }}</div>
            </div>
        @endif

        <div class="footer">
            Thank you for your business. This is a system generated invoice. No signature is required.
        </div>
    </div>
</body>
</html>
