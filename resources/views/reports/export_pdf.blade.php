<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>{{ $title }}</title>
    <style>
        @page { margin: 10mm; }
        body { font-family: DejaVu Sans, sans-serif; color: #111827; font-size: 10px; }
        h1 { font-size: 18px; margin: 0 0 4px; }
        .meta { color: #6b7280; margin-bottom: 12px; }
        table { width: 100%; border-collapse: collapse; table-layout: fixed; }
        th, td { border: 1px solid #d1d5db; padding: 5px; vertical-align: top; overflow-wrap: anywhere; }
        th { background: #f3f4f6; font-size: 9px; text-transform: uppercase; }
        td { font-size: 9px; }
    </style>
</head>
<body>
    <h1>{{ $title }}</h1>
    <div class="meta">Generated at {{ $generatedAt }}</div>
    <table>
        <thead>
            <tr>
                @foreach($headings as $heading)
                    <th>{{ $heading }}</th>
                @endforeach
            </tr>
        </thead>
        <tbody>
            @forelse($rows as $row)
                <tr>
                    @foreach($row as $value)
                        <td>{{ $value }}</td>
                    @endforeach
                </tr>
            @empty
                <tr>
                    <td colspan="{{ count($headings) }}">No data found for the selected filters.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</body>
</html>
