<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Purchase Invoice {{ $purchase->purchase_number }}</title>
    <style>
        body {
            font-family: 'Helvetica', sans-serif;
            font-size: 14px;
            color: #333;
            line-height: 1.5;
            margin: 20px;
        }
        .header {
            width: 100%;
            border-bottom: 2px solid #eee;
            padding-bottom: 20px;
            margin-bottom: 20px;
        }
        .header table {
            width: 100%;
        }
        .company-info {
            text-align: right;
        }
        .company-name {
            font-size: 20px;
            font-weight: bold;
            color: #333;
        }
        .invoice-title {
            font-size: 24px;
            font-weight: bold;
            color: #333;
        }
        .invoice-details {
            margin-top: 5px;
            color: #666;
            font-size: 12px;
        }
        .section {
            margin-bottom: 20px;
        }
        .columns {
            width: 100%;
            margin-bottom: 20px;
        }
        .columns td {
            vertical-align: top;
            width: 50%;
        }
        .heading {
            font-size: 12px;
            font-weight: bold;
            text-transform: uppercase;
            color: #777;
            margin-bottom: 5px;
            border-bottom: 1px solid #ddd;
            padding-bottom: 2px;
        }
        .items-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        .items-table th {
            text-align: left;
            background-color: #f8f9fa;
            padding: 8px;
            border-bottom: 1px solid #ddd;
            font-size: 12px;
            text-transform: uppercase;
            color: #555;
        }
        .items-table td {
            padding: 8px;
            border-bottom: 1px solid #eee;
        }
        .text-right {
            text-align: right;
        }
        .text-center {
            text-align: center;
        }
        .totals {
            width: 100%;
        }
        .totals td {
            padding: 5px 8px;
        }
        .grand-total {
            font-size: 16px;
            font-weight: bold;
            background-color: #eee;
        }
        .payment-card {
            background-color: #f8f9fa;
            border: 1px solid #ddd;
            padding: 10px;
            margin-bottom: 10px;
            border-radius: 4px;
        }
        .payment-grid {
            display: table;
            width: 100%;
        }
        .payment-item {
            display: table-cell;
            padding: 5px 10px;
            vertical-align: top;
        }
        .payment-label {
            font-size: 10px;
            text-transform: uppercase;
            color: #777;
            font-weight: bold;
        }
        .payment-value {
            font-size: 13px;
            color: #333;
            margin-top: 2px;
        }
        .status-badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 4px;
            font-size: 12px;
            font-weight: bold;
        }
        .status-paid {
            background-color: #d4edda;
            color: #155724;
        }
        .status-partial {
            background-color: #fff3cd;
            color: #856404;
        }
        .status-unpaid {
            background-color: #f8d7da;
            color: #721c24;
        }
        @media print {
            body {
                margin: 0;
            }
        }
    </style>
</head>
<body>
    <!-- Header -->
    <div class="header">
        <table>
            <tr>
                <td>
                    <div class="invoice-title">PURCHASE INVOICE</div>
                    <div class="invoice-details">
                        <strong>Purchasing ID:</strong> {{ $purchase->purchase_number }}<br>
                        <strong>Date:</strong> {{ $purchase->purchase_date->format('d M Y') }}
                    </div>
                </td>
                <td class="company-info">
                    <div class="company-name">{{ config('app.name', 'Company Name') }}</div>
                    <div style="font-size: 12px; color: #666; margin-top: 5px;">
                        Inventory Department
                    </div>
                    @php $balance = $purchase->net_total - $purchase->paid_amount; @endphp
                    <div style="margin-top: 10px;">
                        @if($balance <= 0)
                            <span class="status-badge status-paid">PAID</span>
                        @elseif($purchase->paid_amount > 0)
                            <span class="status-badge status-partial">PARTIAL</span>
                        @else
                            <span class="status-badge status-unpaid">UNPAID</span>
                        @endif
                    </div>
                </td>
            </tr>
        </table>
    </div>

    <!-- Supplier & Company Info -->
    <table class="columns">
        <tr>
            <td>
                <div class="heading">Supplier</div>
                <strong>{{ $purchase->supplier->business_name ?? $purchase->supplier->name }}</strong><br>
                @if($purchase->supplier->address)
                    {{ $purchase->supplier->address }}<br>
                @endif
                @if($purchase->supplier->phone)
                    Phone: {{ $purchase->supplier->phone }}<br>
                @endif
                @if($purchase->supplier->email)
                    Email: {{ $purchase->supplier->email }}
                @endif
            </td>
            <td>
                <!-- Empty or additional info -->
            </td>
        </tr>
    </table>

    <!-- Items Table -->
    <table class="items-table">
        <thead>
            <tr>
                <th style="width: 5%;">#</th>
                <th style="width: 45%;">Product Description</th>
                <th style="width: 15%;" class="text-right">Quantity</th>
                <th style="width: 17.5%;" class="text-right">Unit Price</th>
                <th style="width: 17.5%;" class="text-right">Total</th>
            </tr>
        </thead>
        <tbody>
            @foreach($purchase->items as $index => $item)
            <tr>
                <td>{{ $index + 1 }}</td>
                <td><strong>{{ $item->product_name }}</strong></td>
                <td class="text-right">{{ $item->quantity }}</td>
                <td class="text-right">Rs. {{ number_format($item->purchase_price, 2) }}</td>
                <td class="text-right"><strong>Rs. {{ number_format($item->total, 2) }}</strong></td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <!-- Totals -->
    <table class="totals" style="float: right; width: 40%;">
        <tr>
            <td>Subtotal:</td>
            <td class="text-right">Rs. {{ number_format($purchase->sub_total, 2) }}</td>
        </tr>
        @if($purchase->discount_amount > 0)
        <tr>
            <td>Discount ({{ $purchase->discount_type == 'percentage' ? $purchase->discount_value . '%' : 'Fixed' }}):</td>
            <td class="text-right" style="color: #28a745;">- Rs. {{ number_format($purchase->discount_amount, 2) }}</td>
        </tr>
        @endif
        <tr class="grand-total">
            <td><strong>Net Total:</strong></td>
            <td class="text-right"><strong>Rs. {{ number_format($purchase->net_total, 2) }}</strong></td>
        </tr>
        <tr>
            <td>Paid Amount:</td>
            <td class="text-right">Rs. {{ number_format($purchase->paid_amount, 2) }}</td>
        </tr>
        <tr style="font-weight: bold; color: {{ $balance > 0 ? '#dc3545' : '#28a745' }};">
            <td>Balance Due:</td>
            <td class="text-right">Rs. {{ number_format(max(0, $balance), 2) }}</td>
        </tr>
    </table>

    <div style="clear: both;"></div>

    <!-- Payment Details -->
    @if($purchase->payments_data && is_array($purchase->payments_data) && count($purchase->payments_data) > 0)
    <div style="margin-top: 30px; page-break-inside: avoid;">
        <div class="heading">Payment Details</div>
        @foreach($purchase->payments_data as $index => $payment)
        <div class="payment-card">
            <div class="payment-grid">
                <div class="payment-item">
                    <div class="payment-label">Payment #{{ $index + 1 }}</div>
                    <div class="payment-value" style="font-weight: bold; color: #28a745;">Rs. {{ number_format($payment['amount'] ?? 0, 2) }}</div>
                </div>
                <div class="payment-item">
                    <div class="payment-label">Method</div>
                    <div class="payment-value">{{ $payment['method'] ?? 'N/A' }}</div>
                </div>
                <div class="payment-item">
                    <div class="payment-label">Date</div>
                    <div class="payment-value">{{ isset($payment['date']) ? \Carbon\Carbon::parse($payment['date'])->format('d M Y') : 'N/A' }}</div>
                </div>
                @if(!empty($payment['account']))
                <div class="payment-item">
                    <div class="payment-label">Account</div>
                    <div class="payment-value">{{ $payment['account'] }}</div>
                </div>
                @endif
                @if(!empty($payment['note']))
                <div class="payment-item">
                    <div class="payment-label">Note/Ref</div>
                    <div class="payment-value">{{ $payment['note'] }}</div>
                </div>
                @endif
            </div>
        </div>
        @endforeach
    </div>
    @endif

</body>
</html>
