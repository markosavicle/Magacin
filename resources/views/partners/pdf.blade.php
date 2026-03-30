@php use Carbon\Carbon; @endphp
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Partner: {{ $partner->name }}</title>
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
        <h2>Partner: {{ $partner->name }}</h2>
        <div>Tip: {{ $partner->type }}</div>
        <div>Grad: {{ $partner->city }}</div>
        <div>Telefon: {{ $partner->phone }}</div>
        <div>Email: {{ $partner->email }}</div>
        <div>PIB: {{ $partner->vat_number }}</div>
        <div>Kontakt osoba: {{ $partner->contact_person }}</div>
    </div>
    <div class="section">
        <h3>Fakture</h3>
        <table>
            <thead>
                <tr>
                    <th>#</th>
                    <th>Broj fakture</th>
                    <th>Datum</th>
                    <th>Iznos</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                @foreach($invoices as $i => $invoice)
                <tr>
                    <td>{{ $i+1 }}</td>
                    <td>{{ $invoice->invoice_number }}</td>
                    <td>{{ Carbon::parse($invoice->date)->format('d.m.Y') }}</td>
                    <td class="right">{{ number_format($invoice->amount, 2) }} RSD</td>
                    <td>{{ $invoice->status ? 'Plaćeno' : 'Nije plaćeno' }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</body>
</html>
