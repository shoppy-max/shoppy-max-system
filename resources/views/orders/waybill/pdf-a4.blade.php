<!DOCTYPE html>
<html lang="en">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>Waybills A4</title>
    <style>
        @page {
            size: A4 portrait;
            margin: 6mm;
        }

        body {
            margin: 0;
            color: #111827;
            font-family: Arial, sans-serif;
            font-size: 8px;
        }

        .page {
            page-break-after: always;
        }

        .page:last-child {
            page-break-after: auto;
        }

        .canvas {
            position: relative;
            width: 198mm;
            height: 285mm;
        }

        .slot {
            position: absolute;
            width: 97.5mm;
            height: 138.5mm;
            border: 0.4mm solid #111827;
            box-sizing: border-box;
            padding: 2.4mm;
        }

        .slot-1 { left: 0; top: 0; }
        .slot-2 { left: 100.5mm; top: 0; }
        .slot-3 { left: 0; top: 144mm; }
        .slot-4 { left: 100.5mm; top: 144mm; }

        .top-table,
        .meta-table {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
        }

        .top-left {
            width: 58%;
            vertical-align: top;
            padding-right: 1.8mm;
        }

        .top-right {
            width: 42%;
            vertical-align: top;
        }

        .brand {
            font-size: 11px;
            font-weight: 700;
            line-height: 1.05;
            margin: 0;
        }

        .sub {
            margin: 0.8mm 0 0;
            font-size: 6px;
            color: #6b7280;
            line-height: 1.15;
        }

        .collect-box,
        .waybill-box,
        .ship-box,
        .items-box,
        .meta-card {
            border: 0.25mm solid #d1d5db;
            box-sizing: border-box;
        }

        .collect-box {
            padding: 1.2mm;
            text-align: center;
        }

        .mini-k {
            font-size: 5.8px;
            text-transform: uppercase;
            color: #6b7280;
            letter-spacing: 0.25px;
        }

        .mini-v {
            margin-top: 0.5mm;
            font-size: 8px;
            font-weight: 700;
            line-height: 1.1;
        }

        .waybill-box {
            margin-top: 1.4mm;
            padding: 1.4mm;
            text-align: center;
        }

        .waybill-id {
            margin: 0 0 0.8mm;
            font-size: 10px;
            font-weight: 700;
            line-height: 1.05;
        }

        .barcode-wrap {
            height: 11mm;
        }

        .barcode-wrap img {
            width: 100%;
            height: 11mm;
            object-fit: contain;
            display: block;
        }

        .ship-box {
            margin-top: 1.6mm;
            padding: 1.5mm 1.8mm;
            height: 34mm;
            overflow: hidden;
        }

        .section-title {
            margin: 0 0 0.9mm;
            font-size: 5.8px;
            font-weight: 700;
            text-transform: uppercase;
            color: #6b7280;
            letter-spacing: 0.3px;
        }

        .recipient-name {
            margin: 0 0 0.7mm;
            font-size: 8px;
            font-weight: 700;
            line-height: 1.1;
        }

        .line {
            margin: 0.35mm 0;
            font-size: 6.6px;
            line-height: 1.15;
        }

        .meta-table {
            margin-top: 1.6mm;
        }

        .meta-table td {
            width: 33.333%;
            padding-right: 1.4mm;
            vertical-align: top;
        }

        .meta-table td:last-child {
            padding-right: 0;
        }

        .meta-card {
            padding: 1.2mm;
            min-height: 10.5mm;
        }

        .items-box {
            margin-top: 1.6mm;
            padding: 1.5mm 1.8mm;
            height: 21mm;
            overflow: hidden;
        }

        .items-text {
            font-size: 6.5px;
            line-height: 1.15;
            max-height: 13mm;
            overflow: hidden;
        }
    </style>
</head>
<body>
@php
    $generator = new \Picqer\Barcode\BarcodeGeneratorPNG();
@endphp

@foreach($orders->chunk(4) as $pageOrders)
    <div class="page">
        <div class="canvas">
            @for($index = 0; $index < 4; $index++)
                @php $order = $pageOrders->get($index); @endphp
                @if($order)
                    @php
                        $cityName = $order->city->city_name ?? $order->customer_city ?? 'N/A';
                        $collectible = max((float) ($order->total_amount ?? 0) - (float) ($order->paid_amount ?? 0), 0);
                        $collectText = strtoupper((string) ($order->payment_method ?? '')) === 'COD' && $collectible > 0
                            ? 'Rs. ' . number_format($collectible, 2)
                            : 'Prepaid';
                        $itemsText = $order->items
                            ->map(fn ($item) => $item->quantity . ' x ' . $item->product_name)
                            ->implode(', ');
                        $paymentMethod = (string) ($order->payment_method ?? 'N/A');
                    @endphp
                    <div class="slot slot-{{ $index + 1 }}">
                        <table class="top-table">
                            <tr>
                                <td class="top-left">
                                    <p class="brand">{{ config('app.name', 'ShoppyMax') }}</p>
                                    <p class="sub">{{ $order->courier->name ?? 'Courier' }} Waybill</p>
                                </td>
                                <td class="top-right">
                                    <div class="collect-box">
                                        <div class="mini-k">Collect Amount</div>
                                        <div class="mini-v">{{ $collectText }}</div>
                                    </div>
                                </td>
                            </tr>
                        </table>

                        <div class="waybill-box">
                            <p class="waybill-id">{{ $order->waybill_number }}</p>
                            <div class="barcode-wrap">
                                <img src="data:image/png;base64,{{ base64_encode($generator->getBarcode($order->waybill_number, $generator::TYPE_CODE_128)) }}" alt="Barcode {{ $order->waybill_number }}">
                            </div>
                        </div>

                        <div class="ship-box">
                            <p class="section-title">Ship To</p>
                            <p class="recipient-name">{{ $order->customer_name }}</p>
                            <p class="line">{{ $order->customer_phone }}</p>
                            <p class="line">{{ \Illuminate\Support\Str::limit((string) $order->customer_address, 70) }}</p>
                            <p class="line">{{ $cityName }}</p>
                        </div>

                        <table class="meta-table">
                            <tr>
                                <td>
                                    <div class="meta-card">
                                        <p class="section-title">Order ID</p>
                                        <p class="line">{{ $order->order_number }}</p>
                                    </div>
                                </td>
                                <td>
                                    <div class="meta-card">
                                        <p class="section-title">Payment</p>
                                        <p class="line">{{ $paymentMethod }}</p>
                                    </div>
                                </td>
                                <td>
                                    <div class="meta-card">
                                        <p class="section-title">Printed</p>
                                        <p class="line">{{ optional($generatedAt)->format('Y-m-d H:i') }}</p>
                                    </div>
                                </td>
                            </tr>
                        </table>

                        <div class="items-box">
                            <p class="section-title">Items</p>
                            <div class="items-text">{{ $itemsText }}</div>
                        </div>
                    </div>
                @endif
            @endfor
        </div>
    </div>
@endforeach
</body>
</html>
