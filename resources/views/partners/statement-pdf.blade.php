@php use Carbon\Carbon; @endphp
<!DOCTYPE html>
<html lang="sr">
<head>
    <meta charset="UTF-8">
    <title>Izvod: {{ $partner->name }}</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 11px; color: #333; }
        .header { text-align: center; margin-bottom: 20px; border-bottom: 2px solid #333; padding-bottom: 10px; }
        .header h2 { margin: 0 0 5px 0; font-size: 18px; }
        .partner-info { margin-bottom: 15px; }
        .partner-info div { margin-bottom: 2px; }
        .period { margin-bottom: 15px; font-style: italic; color: #555; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        th, td { border: 1px solid #999; padding: 5px 8px; text-align: left; }
        th { background: #f0f0f0; font-size: 10px; text-transform: uppercase; }
        .right { text-align: right; }
        .totals { margin-top: 10px; }
        .totals table { width: auto; float: right; }
        .totals td { font-weight: bold; padding: 4px 12px; }
        .footer { margin-top: 30px; text-align: right; font-size: 10px; color: #777; }
        .positive { color: #16a34a; }
        .negative { color: #dc2626; }
    </style>
</head>
<body>
    <div class="header">
        <h2>Izvod stanja - {{ $partner->name }}</h2>
    </div>

    <div class="partner-info">
        @if($partner->address)<div><strong>Adresa:</strong> {{ $partner->address }}{{ $partner->city ? ', ' . $partner->city : '' }}</div>@endif
        @if($partner->vat_number)<div><strong>PIB:</strong> {{ $partner->vat_number }}</div>@endif
        @if($partner->phone)<div><strong>Telefon:</strong> {{ $partner->phone }}</div>@endif
        @if($partner->email)<div><strong>Email:</strong> {{ $partner->email }}</div>@endif
        @if($partner->contact_person)<div><strong>Kontakt osoba:</strong> {{ $partner->contact_person }}</div>@endif
    </div>

    <div class="period">
        @if($dateFrom && $dateTo)
            Period: {{ Carbon::parse($dateFrom)->format('d.m.Y') }} - {{ Carbon::parse($dateTo)->format('d.m.Y') }}
        @elseif($dateFrom)
            Od: {{ Carbon::parse($dateFrom)->format('d.m.Y') }}
        @elseif($dateTo)
            Do: {{ Carbon::parse($dateTo)->format('d.m.Y') }}
        @else
            Celokupan period
        @endif
    </div>

    <table>
        <thead>
            <tr>
                <th>#</th>
                <th>Datum</th>
                <th>Opis</th>
                <th class="right">Zaduženje</th>
                <th class="right">Razduženje</th>
                <th class="right">Saldo</th>
            </tr>
        </thead>
        <tbody>
            @php
                $runningBalance = 0;
                $totalZaduzenje = 0;
                $totalRazduzenje = 0;
            @endphp
            @forelse($entries as $i => $entry)
                @php
                    $runningBalance += $entry->zaduzenje - $entry->razduzenje;
                    $totalZaduzenje += $entry->zaduzenje;
                    $totalRazduzenje += $entry->razduzenje;
                @endphp
                <tr>
                    <td>{{ $i + 1 }}</td>
                    <td>{{ Carbon::parse($entry->date)->format('d.m.Y') }}</td>
                    <td>{{ $entry->description }}</td>
                    <td class="right">{{ $entry->zaduzenje > 0 ? number_format($entry->zaduzenje, 2) : '' }}</td>
                    <td class="right">{{ $entry->razduzenje > 0 ? number_format($entry->razduzenje, 2) : '' }}</td>
                    <td class="right {{ $runningBalance > 0 ? 'positive' : ($runningBalance < 0 ? 'negative' : '') }}">
                        {{ number_format($runningBalance, 2) }}
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="6" style="text-align: center; color: #999;">Nema stavki za prikaz</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    @if($entries->count() > 0)
    <div class="totals" style="clear: both;">
        <table>
            <tr>
                <td>Ukupno zaduženje:</td>
                <td class="right">{{ number_format($totalZaduzenje, 2) }} RSD</td>
            </tr>
            <tr>
                <td>Ukupno razduženje:</td>
                <td class="right">{{ number_format($totalRazduzenje, 2) }} RSD</td>
            </tr>
            <tr style="border-top: 2px solid #333;">
                <td>Krajnje stanje:</td>
                <td class="right {{ $runningBalance > 0 ? 'positive' : ($runningBalance < 0 ? 'negative' : '') }}">
                    {{ number_format($runningBalance, 2) }} RSD
                </td>
            </tr>
        </table>
    </div>
    @endif

    <div class="footer">
        Generisano: {{ Carbon::now()->format('d.m.Y H:i') }}
    </div>
</body>
</html>
