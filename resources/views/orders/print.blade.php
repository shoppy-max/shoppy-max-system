<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Invoice {{ $order->order_number }}</title>
    <style>
        :root {
            color-scheme: light;
        }
        body {
            margin: 0;
            background: #f3f4f6;
            color: #111827;
            font-family: Arial, Helvetica, sans-serif;
        }
        .toolbar {
            max-width: 860px;
            margin: 16px auto 0;
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 12px;
        }
        .toolbar a,
        .toolbar button {
            border: 0;
            border-radius: 6px;
            padding: 10px 14px;
            font-size: 12px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.03em;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
        }
        .btn-back {
            background: #e5e7eb;
            color: #111827;
        }
        .btn-print {
            background: #166534;
            color: #fff;
        }
        .paper {
            max-width: 860px;
            margin: 12px auto 24px;
            background: #fff;
            border: 1px solid #e5e7eb;
            border-radius: 10px;
            overflow: hidden;
        }
        .header {
            padding: 28px 32px;
            border-bottom: 1px solid #e5e7eb;
            display: flex;
            justify-content: space-between;
            gap: 24px;
        }
        .title {
            margin: 0;
            font-size: 28px;
            font-weight: 800;
        }
        .meta {
            margin-top: 4px;
            font-size: 13px;
            color: #4b5563;
        }
        .badge {
            margin-top: 10px;
            display: inline-block;
            border-radius: 999px;
            padding: 4px 10px;
            font-size: 11px;
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
            font-size: 13px;
            color: #4b5563;
            line-height: 1.45;
        }
        .company strong {
            display: block;
            font-size: 18px;
            color: #111827;
            margin-bottom: 6px;
        }
        .sections {
            padding: 24px 32px 8px;
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 24px;
        }
        .label {
            margin: 0 0 8px;
            font-size: 11px;
            letter-spacing: 0.05em;
            text-transform: uppercase;
            color: #6b7280;
            font-weight: 700;
        }
        .value {
            font-size: 14px;
            color: #374151;
            line-height: 1.5;
        }
        .value strong {
            color: #111827;
            font-size: 16px;
        }
        .table-wrap {
            padding: 8px 32px 0;
        }
        table {
            width: 100%;
            border-collapse: collapse;
        }
        thead th {
            text-align: left;
            font-size: 11px;
            text-transform: uppercase;
            letter-spacing: 0.04em;
            color: #6b7280;
            background: #f9fafb;
            border-bottom: 1px solid #e5e7eb;
            padding: 10px 12px;
        }
        tbody td {
            border-bottom: 1px solid #f3f4f6;
            padding: 10px 12px;
            font-size: 13px;
            color: #111827;
        }
        .text-right { text-align: right; }
        .text-center { text-align: center; }
        .totals {
            padding: 18px 32px 24px;
            display: flex;
            justify-content: flex-end;
        }
        .totals-box {
            width: 340px;
            border-top: 1px solid #e5e7eb;
            padding-top: 12px;
        }
        .totals-row {
            display: flex;
            justify-content: space-between;
            padding: 6px 0;
            font-size: 13px;
            color: #374151;
        }
        .totals-row strong {
            font-size: 18px;
            color: #111827;
        }
        .note {
            padding: 0 32px 24px;
            font-size: 13px;
            color: #374151;
        }
        .note-box {
            margin-top: 6px;
            background: #fffbeb;
            border: 1px solid #fef3c7;
            border-radius: 8px;
            padding: 10px;
        }
        .footer {
            border-top: 1px solid #e5e7eb;
            padding: 18px 32px 24px;
            font-size: 12px;
            color: #6b7280;
        }
        .muted-line {
            display: block;
            margin-top: 2px;
            font-size: 11px;
            color: #6b7280;
        }
        .tracking-list {
            margin-top: 6px;
            font-size: 10px;
            line-height: 1.45;
            color: #4b5563;
        }
        .tracking-unit {
            display: inline-block;
            margin: 0 4px 4px 0;
            padding: 2px 6px;
            border: 1px solid #d1d5db;
            border-radius: 10px;
            background: #f9fafb;
            font-family: monospace;
            font-size: 10px;
            color: #111827;
        }
        @media print {
            body {
                background: #fff;
            }
            .no-print {
                display: none !important;
            }
            .paper {
                margin: 0;
                max-width: 100%;
                border: 0;
                border-radius: 0;
            }
            @page {
                size: A4;
                margin: 10mm;
            }
        }
    </style>
</head>
<body>
    <?php $autoPrint = request()->boolean('autoprint'); ?>
    <div class="toolbar no-print">
        <a href="{{ route('orders.show', $order) }}" class="btn-back">Back to Order</a>
        <button type="button" class="btn-print" onclick="window.print()">{{ $autoPrint ? 'Print Again' : 'Print' }}</button>
    </div>

    <div class="paper">
        <div class="header">
            <div>
                <h1 class="title">Invoice</h1>
                <div class="meta">Order #{{ $order->order_number }}</div>
                <div class="meta">Date: {{ optional($order->order_date)->format('d M, Y') }}</div>
                <?php
                    $status = strtolower((string) ($order->status ?? 'pending'));
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
                ?>
                <span class="badge {{ [
                    'pending' => 'badge-pending',
                    'confirm' => 'badge-confirm',
                    'hold' => 'badge-hold',
                    'cancel' => 'badge-cancelled',
                ][$status] ?? 'badge-other' }}">{{ ucfirst($status) }}</span>
            </div>
            <div class="company">
                <strong>{{ config('app.name', 'ShoppyMax') }}</strong>
            </div>
        </div>

        <div class="sections">
            <div>
                <p class="label">Invoice To</p>
                <div class="value">
                    <strong>{{ $order->customer->name ?? $order->customer_name }}</strong><br>
                    {{ $order->customer->address ?? $order->customer_address }}<br>
                    {{ $order->customer->mobile ?? $order->customer_phone }}<br>
                    {{ $order->customer_city ?? $order->customer->city }}{{ $order->customer_district ? ', ' . $order->customer_district : '' }}{{ $order->customer_province ? ', ' . $order->customer_province : '' }}
                </div>
            </div>
            <div>
                @if($order->order_type === 'reseller' && $order->reseller)
                    <p class="label">Reseller Info</p>
                    <div class="value">
                        <strong>{{ $order->reseller->business_name ?: $order->reseller->name }}</strong><br>
                        Contact: {{ $order->reseller->name }}<br>
                        Mobile: {{ $order->reseller->mobile }}<br>
                        Account: {{ $order->reseller->reseller_type === 'direct_reseller' ? 'Direct Reseller' : 'Reseller' }}<br>
                        Delivery Status: {{ $deliveryLabels[$deliveryStatus] ?? 'Pending' }}
                    </div>
                @else
                    <p class="label">Order Info</p>
                    <div class="value">
                        Type: {{ ucfirst($order->order_type) }}<br>
                        Payment: {{ $order->payment_method === 'COD' ? 'Cash on Delivery (COD)' : $order->payment_method }}<br>
                        @if($order->courier)Courier: {{ $order->courier->name }}<br>@endif
                        Call Status: {{ ucfirst((string) $order->call_status) }}<br>
                        Delivery Status: {{ $deliveryLabels[$deliveryStatus] ?? 'Pending' }}<br>
                        Created By: {{ $order->user->name ?? 'System' }}
                    </div>
                @endif
            </div>
        </div>

        <div class="table-wrap">
            <table>
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
        </div>

        <div class="totals">
            <div class="totals-box">
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
                @if($order->order_type === 'reseller')
                    <div class="totals-row">
                        <span>Total Commission</span>
                        <span>LKR {{ number_format($order->total_commission, 2) }}</span>
                    </div>
                @endif
                <div class="totals-row">
                    <span>Subtotal</span>
                    <span>LKR {{ number_format($subTotalBeforeDiscount, 2) }}</span>
                </div>
                <div class="totals-row">
                    <span>Discount</span>
                    <span>- LKR {{ number_format($discountAmount, 2) }}</span>
                </div>
                <div class="totals-row">
                    <span>Courier Charge</span>
                    <span>LKR {{ number_format($order->courier_charge, 2) }}</span>
                </div>
                <div class="totals-row">
                    <span>Paid Amount</span>
                    <span>LKR {{ number_format($paidAmount, 2) }}</span>
                </div>
                @if($returnFeeDeduction > 0)
                    <div class="totals-row">
                        <span>Return Fee Penalty</span>
                        <span>- LKR {{ number_format($returnFeeDeduction, 2) }}</span>
                    </div>
                @endif
                <div class="totals-row">
                    <span>{{ $order->payment_method === 'COD' ? 'Remaining (COD Collect)' : 'Remaining Amount' }}</span>
                    <span>LKR {{ number_format($remainingAmount, 2) }}</span>
                </div>
                <div class="totals-row">
                    <strong>Grand Total</strong>
                    <strong>LKR {{ number_format($order->total_amount, 2) }}</strong>
                </div>
            </div>
        </div>

        @if(is_array($order->payments_data) && count($order->payments_data) > 0)
            <div class="note">
                <p class="label">Payment Entries</p>
                <table>
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
                <p class="label">Sales Note / Remarks</p>
                <div class="note-box">{{ $order->sales_note }}</div>
            </div>
        @endif

        <div class="footer">
            Thank you for your business. This is a system generated invoice. No signature is required.
        </div>
    </div>
    @if($autoPrint)
        <script>
            window.addEventListener('load', function () {
                setTimeout(function () {
                    window.print();
                }, 120);
            });
        </script>
    @endif
</body>
</html>
