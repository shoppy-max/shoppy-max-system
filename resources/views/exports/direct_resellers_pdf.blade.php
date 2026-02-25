<!DOCTYPE html>
<html>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>Direct Reseller List</title>
    <style>
        body {
            font-family: sans-serif;
            font-size: 10pt;
        }
        h1 {
            text-align: center;
            font-size: 16pt;
            margin-bottom: 20px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
        }
        th, td {
            border: 1px solid #444;
            padding: 6px;
            text-align: left;
            vertical-align: top;
        }
        th {
            background-color: #f2f2f2;
            font-weight: bold;
        }
        .meta {
            margin-bottom: 20px;
            font-size: 9pt;
            color: #555;
            text-align: right;
        }
        .amount {
            text-align: right;
        }
    </style>
</head>
<body>
    <h1>Direct Reseller List</h1>
    <div class="meta">
        Generated on: {{ now()->format('Y-m-d H:i:s') }}
    </div>

    <table>
        <thead>
            <tr>
                <th>Business Name</th>
                <th>Name</th>
                <th>Mobile</th>
                <th>City</th>
                <th>District</th>
                <th>Province</th>
                <th>Due Amount</th>
            </tr>
        </thead>
        <tbody>
            @foreach($resellers as $reseller)
                <tr>
                    <td>{{ $reseller->business_name }}</td>
                    <td>{{ $reseller->name }}</td>
                    <td>{{ $reseller->mobile }}</td>
                    <td>{{ $reseller->city ?? '-' }}</td>
                    <td>{{ $reseller->district ?? '-' }}</td>
                    <td>{{ $reseller->province ?? '-' }}</td>
                    <td class="amount">{{ number_format($reseller->due_amount, 2) }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</body>
</html>
