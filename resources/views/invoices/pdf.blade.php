@php use Carbon\Carbon; @endphp
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Faktura #{{ $invoice->invoice_number }}</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 12px; }
        .header { text-align: center; margin-bottom: 20px; }
        .section { margin-bottom: 20px; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        th, td { border: 1px solid #333; padding: 6px; text-align: left; }
        th { background: #f0f0f0; }
        .right { text-align: right; }
    </style>
</head>
<body>
    <div class="header">
        <h2>Faktura #{{ $invoice->invoice_number }}</h2>
        <div>Datum: {{ Carbon::parse($invoice->date)->format('d.m.Y') }}</div>
    </div>
    <div class="section">
        <strong>Partner:</strong> {{ $invoice->partner->name }}<br>
        <strong>Tip:</strong> {{ $invoice->type == 'out' ? 'Izlazna' : 'Ulazna' }}<br>
        <strong>Napomena:</strong> {{ $invoice->note }}
    </div>
    <div class="section">
        <table>
            <thead>
                <tr>
                    <th>#</th>
                    <th>Stavka</th>
                    <th>Količina</th>
                    <th>Cena</th>
                    <th>Ukupno</th>
                </tr>
            </thead>
            <tbody>
                @foreach($invoice->items as $i => $item)
                <tr>
                    <td>{{ $i+1 }}</td>
                    <td>{{ $item->name }}</td>
                    <td class="right">{{ $item->quantity }}</td>
                    <td class="right">{{ number_format($item->price, 2) }} RSD</td>
                    <td class="right">{{ number_format($item->total, 2) }} RSD</td>
                </tr>
                @endforeach
            </tbody>
        </table>
        <div class="right"><strong>Ukupno: {{ number_format($invoice->amount, 2) }} RSD</strong></div>
    </div>
</body>
</html>
