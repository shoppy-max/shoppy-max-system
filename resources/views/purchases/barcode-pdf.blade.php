<!DOCTYPE html>
<html>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>Purchase Unit Labels</title>
    <style>
        @page {
            size: A4 portrait;
            margin: 6mm;
        }

        body {
            margin: 0;
            font-family: Arial, sans-serif;
            color: #111827;
        }

        .sheet {
            width: 100%;
        }

        .label-row {
            width: 100%;
            clear: both;
        }

        .label-item {
            float: left;
            width: 34mm;
            height: 25mm;
            margin-right: 2mm;
            margin-bottom: 2mm;
            border: 0.2mm solid #d1d5db;
            padding: 1.1mm 1.5mm 1mm;
            text-align: center;
            box-sizing: border-box;
            overflow: hidden;
            page-break-inside: avoid;
        }

        .label-item.last-in-row {
            margin-right: 0;
        }

        .label-row::after {
            content: "";
            display: block;
            clear: both;
        }

        .product-name {
            font-size: 8px;
            font-weight: 700;
            line-height: 1.1;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .variant-info {
            margin-top: 0.5mm;
            min-height: 2.4mm;
            font-size: 6.2px;
            line-height: 1.1;
            color: #4b5563;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .sku {
            margin-top: 0.4mm;
            font-size: 5.8px;
            color: #6b7280;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .barcode-wrap {
            margin: 0.6mm 0 0.4mm;
            height: 9.2mm;
        }

        .barcode-img {
            width: 100%;
            height: 9.2mm;
            object-fit: contain;
            display: block;
        }

        .unit-code {
            font-family: "Courier New", monospace;
            font-size: 6.5px;
            font-weight: 700;
            letter-spacing: 0.2px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .status {
            margin-top: 0.3mm;
            font-size: 5.5px;
            text-transform: uppercase;
            letter-spacing: 0.3px;
            color: #2563eb;
        }
    </style>
</head>
<body>
    @php
        $generator = new \Picqer\Barcode\BarcodeGeneratorPNG();
    @endphp

    <div class="sheet">
        @foreach(collect($units)->chunk(5) as $row)
            <div class="label-row">
                @foreach($row as $unit)
                    <div class="label-item {{ $loop->last ? 'last-in-row' : '' }}">
                        <div class="product-name">{{ $unit->product_name_snapshot ?: ($unit->productVariant?->product?->name ?? 'Product') }}</div>
                        <div class="variant-info">{{ $unit->variant_label_snapshot ?: '-' }}</div>
                        <div class="sku">{{ $unit->sku_snapshot ?: ($unit->productVariant?->sku ?? '-') }}</div>

                        <div class="barcode-wrap">
                            <img
                                class="barcode-img"
                                src="data:image/png;base64,{{ base64_encode($generator->getBarcode($unit->unit_code, $generator::TYPE_CODE_128)) }}"
                                alt="Barcode for {{ $unit->unit_code }}"
                            >
                        </div>

                        <div class="unit-code">{{ $unit->unit_code }}</div>
                        <div class="status">{{ str_replace('_', ' ', $unit->status) }}</div>
                    </div>
                @endforeach
            </div>
        @endforeach
    </div>
</body>
</html>
