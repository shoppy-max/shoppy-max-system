<!DOCTYPE html>
<html lang="en">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>Waybills 4x6</title>
    <style>
        @page {
            size: 101.6mm 152.4mm;
            margin: 0;
        }

        body {
            margin: 0;
            color: #111827;
            font-family: Arial, sans-serif;
            font-size: 9px;
        }

        .sheet {
            position: relative;
            width: 85.6mm;
            height: 138mm;
            margin: 6mm 8mm 0;
            border: 0.5mm solid #111827;
            box-sizing: border-box;
            overflow: hidden;
        }

        .box {
            position: absolute;
            border: 0.35mm solid #d1d5db;
            border-radius: 2mm;
            box-sizing: border-box;
            overflow: hidden;
        }

        .dark-box {
            border-color: #111827;
        }

        .pad {
            padding: 2mm;
            box-sizing: border-box;
        }

        .brand {
            position: absolute;
            left: 3mm;
            top: 3mm;
            width: 44mm;
        }

        .brand h1 {
            margin: 0;
            font-size: 12px;
            line-height: 1.05;
        }

        .brand p {
            margin: 1mm 0 0;
            font-size: 6.5px;
            color: #6b7280;
            line-height: 1.15;
        }

        .collect {
            left: 48mm;
            top: 3mm;
            width: 34.6mm;
            height: 13mm;
            text-align: center;
        }

        .k {
            margin: 0;
            font-size: 6px;
            text-transform: uppercase;
            letter-spacing: 0.3px;
            color: #6b7280;
        }

        .v {
            margin: 1mm 0 0;
            font-size: 10px;
            font-weight: 700;
            line-height: 1.1;
        }

        .barcode {
            left: 3mm;
            top: 18mm;
            width: 79.6mm;
            height: 24mm;
            text-align: center;
        }

        .waybill-id {
            margin: 0 0 1.2mm;
            font-size: 11px;
            font-weight: 700;
            line-height: 1.05;
        }

        .barcode img {
            display: block;
            width: 100%;
            height: 11.5mm;
            object-fit: contain;
        }

        .ship {
            left: 3mm;
            top: 45mm;
            width: 79.6mm;
            height: 32mm;
        }

        .recipient-name {
            margin: 0 0 1.3mm;
            font-size: 10px;
            font-weight: 700;
            line-height: 1.1;
        }

        .line {
            margin: 0.8mm 0;
            font-size: 7px;
            line-height: 1.2;
        }

        .meta {
            top: 80mm;
            height: 13mm;
        }

        .meta-order {
            left: 3mm;
            width: 24.8mm;
        }

        .meta-payment {
            left: 30mm;
            width: 24.8mm;
        }

        .meta-printed {
            left: 57mm;
            width: 25.6mm;
        }

        .meta .v {
            font-size: 8px;
        }

        .items {
            left: 3mm;
            top: 96mm;
            width: 79.6mm;
            height: 39mm;
        }

        .items-text {
            font-size: 7px;
            line-height: 1.2;
            max-height: 30mm;
            overflow: hidden;
        }
    </style>
</head>
<body>
@php
    $generator = new \Picqer\Barcode\BarcodeGeneratorPNG();
@endphp

@foreach($orders as $order)
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

    <div class="sheet">
        <div class="brand">
            <h1>{{ config('app.name', 'ShoppyMax') }}</h1>
            <p>{{ $order->courier->name ?? 'Courier' }} Waybill</p>
        </div>

        <div class="box dark-box collect">
            <div class="pad">
                <p class="k">Collect Amount</p>
                <p class="v">{{ $collectText }}</p>
            </div>
        </div>

        <div class="box dark-box barcode">
            <div class="pad">
                <p class="waybill-id">{{ $order->waybill_number }}</p>
                <img src="data:image/png;base64,{{ base64_encode($generator->getBarcode($order->waybill_number, $generator::TYPE_CODE_128)) }}" alt="Barcode {{ $order->waybill_number }}">
            </div>
        </div>

        <div class="box ship">
            <div class="pad">
                <p class="k">Deliver To</p>
                <p class="recipient-name">{{ $order->customer_name }}</p>
                <p class="line">{{ $order->customer_phone }}</p>
                <p class="line">{{ \Illuminate\Support\Str::limit((string) $order->customer_address, 78) }}</p>
                <p class="line">{{ $cityName }}</p>
            </div>
        </div>

        <div class="box meta meta-order">
            <div class="pad">
                <p class="k">Order ID</p>
                <p class="v">{{ $order->order_number }}</p>
            </div>
        </div>

        <div class="box meta meta-payment">
            <div class="pad">
                <p class="k">Payment</p>
                <p class="v">{{ $paymentMethod }}</p>
            </div>
        </div>

        <div class="box meta meta-printed">
            <div class="pad">
                <p class="k">Printed</p>
                <p class="v">{{ optional($generatedAt)->format('Y-m-d H:i') }}</p>
            </div>
        </div>

        <div class="box items">
            <div class="pad">
                <p class="k">Items</p>
                <div class="items-text">{{ $itemsText }}</div>
            </div>
        </div>
    </div>

    @if(! $loop->last)
        <div style="page-break-after: always;"></div>
    @endif
@endforeach
</body>
</html>
