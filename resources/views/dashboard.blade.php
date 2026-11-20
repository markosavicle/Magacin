@php
    use App\Services\InvoicePaymentStatus;

    $money = fn ($amount) => number_format((float) $amount, 2) . ' RSD';
    $badge = [
        InvoicePaymentStatus::PAID => 'bg-green-100 text-green-800',
        InvoicePaymentStatus::PARTIAL => 'bg-yellow-100 text-yellow-800',
        InvoicePaymentStatus::UNPAID => 'bg-red-100 text-red-800',
    ];
@endphp

<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Dashboard') }}
        </h2>
    </x-slot>

    <div class="py-8 sm:py-12">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">

            {{-- Quick actions --}}
            <div class="flex flex-wrap gap-3" data-testid="quick-actions">
                <x-primary-link href="{{ route('invoices.create') }}">+ Nova faktura</x-primary-link>
                <x-primary-link href="{{ route('transactions.create') }}" class="!bg-green-600 hover:!bg-green-700">+ Evidentiraj uplatu</x-primary-link>
                <x-primary-link href="{{ route('partners.index') }}" class="!bg-white !text-gray-700 !border-gray-300 hover:!bg-gray-50">Pregled partnera</x-primary-link>
                <x-primary-link href="{{ route('partners.create') }}" class="!bg-white !text-gray-700 !border-gray-300 hover:!bg-gray-50">+ Novi partner</x-primary-link>
            </div>

            {{-- KPI cards --}}
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                <div class="bg-white shadow-sm sm:rounded-lg p-5">
                    <p class="text-sm font-medium text-gray-500">Partneri</p>
                    <p class="mt-2 text-2xl font-semibold text-gray-900">{{ $kpi['partners'] }}</p>
                    <p class="mt-1 text-xs text-gray-500">Kupaca: {{ $kpi['customers'] }} · Dobavljača: {{ $kpi['suppliers'] }}</p>
                </div>

                <div class="bg-white shadow-sm sm:rounded-lg p-5">
                    <p class="text-sm font-medium text-gray-500">Ukupno fakturisano</p>
                    <p class="mt-2 text-2xl font-semibold text-gray-900">{{ $money($kpi['invoiced']) }}</p>
                    <p class="mt-1 text-xs text-gray-500">Izlaznih faktura: {{ $kpi['invoicedCount'] }}</p>
                </div>

                <div class="bg-white shadow-sm sm:rounded-lg p-5">
                    <p class="text-sm font-medium text-gray-500">Naplaćeno</p>
                    <p class="mt-2 text-2xl font-semibold text-green-700">{{ $money($kpi['collected']) }}</p>
                    <p class="mt-1 text-xs text-gray-500">
                        @if ($kpi['collectedShare'] !== null)
                            {{ $kpi['collectedShare'] }}% fakturisanog iznosa
                        @else
                            Još nema izlaznih faktura
                        @endif
                    </p>
                </div>

                <div class="bg-white shadow-sm sm:rounded-lg p-5">
                    <p class="text-sm font-medium text-gray-500">Otvoreno potraživanje</p>
                    <p class="mt-2 text-2xl font-semibold {{ $kpi['receivable'] > 0 ? 'text-red-700' : 'text-gray-900' }}">{{ $money($kpi['receivable']) }}</p>
                    <p class="mt-1 text-xs text-gray-500">
                        Neplaćenih faktura: {{ $kpi['openInvoices'] }} · Dugujemo dobavljačima: {{ $money($kpi['payable']) }}
                    </p>
                </div>
            </div>

            <div class="grid grid-cols-1 xl:grid-cols-2 gap-6">
                {{-- Latest invoices --}}
                <div class="bg-white shadow-sm sm:rounded-lg overflow-hidden">
                    <div class="flex items-center justify-between px-5 py-4 border-b border-gray-100">
                        <h3 class="font-semibold text-gray-800">Poslednje fakture</h3>
                        <a href="{{ route('invoices.index') }}" class="text-sm text-indigo-600 hover:text-indigo-900">Sve fakture →</a>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200 text-sm">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Broj</th>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Partner</th>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Datum</th>
                                    <th class="px-4 py-2 text-right text-xs font-medium text-gray-500 uppercase">Iznos</th>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                @forelse ($recentInvoices as $invoice)
                                    @php $status = $statuses[$invoice->id]['status'] ?? InvoicePaymentStatus::UNPAID; @endphp
                                    <tr>
                                        <td class="px-4 py-3 whitespace-nowrap">
                                            <a href="{{ route('invoices.show', $invoice) }}" class="font-semibold text-indigo-600 hover:text-indigo-900">{{ $invoice->invoice_number }}</a>
                                            <span class="block text-xs text-gray-400">{{ $invoice->type === 'out' ? 'Izlazna' : 'Ulazna' }}</span>
                                        </td>
                                        <td class="px-4 py-3">
                                            <a href="{{ route('partners.show', $invoice->partner) }}" class="text-gray-900 hover:text-indigo-700">{{ $invoice->partner->name }}</a>
                                        </td>
                                        <td class="px-4 py-3 whitespace-nowrap text-gray-700">{{ $invoice->date->format('d.m.Y') }}</td>
                                        <td class="px-4 py-3 whitespace-nowrap text-right text-gray-900">{{ $money($invoice->amount) }}</td>
                                        <td class="px-4 py-3 whitespace-nowrap">
                                            <span class="inline-flex px-2 py-0.5 rounded-full text-xs font-medium {{ $badge[$status] }}">{{ InvoicePaymentStatus::label($status) }}</span>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="px-4 py-6 text-center text-gray-500">
                                            Još nema faktura. <a href="{{ route('invoices.create') }}" class="text-indigo-600 hover:text-indigo-900">Dodajte prvu.</a>
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>

                {{-- Latest payments --}}
                <div class="bg-white shadow-sm sm:rounded-lg overflow-hidden">
                    <div class="flex items-center justify-between px-5 py-4 border-b border-gray-100">
                        <h3 class="font-semibold text-gray-800">Poslednje uplate i isplate</h3>
                        <a href="{{ route('transactions.index') }}" class="text-sm text-indigo-600 hover:text-indigo-900">Sve transakcije →</a>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200 text-sm">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Datum</th>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Partner</th>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Tip</th>
                                    <th class="px-4 py-2 text-right text-xs font-medium text-gray-500 uppercase">Iznos</th>
                                    <th class="px-4 py-2"></th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                @forelse ($recentPayments as $payment)
                                    <tr>
                                        <td class="px-4 py-3 whitespace-nowrap text-gray-700">{{ $payment->date?->format('d.m.Y') }}</td>
                                        <td class="px-4 py-3">
                                            <a href="{{ route('partners.show', $payment->partner) }}" class="text-gray-900 hover:text-indigo-700">{{ $payment->partner->name }}</a>
                                        </td>
                                        <td class="px-4 py-3 whitespace-nowrap">
                                            @if ($payment->type === 'uplata')
                                                <span class="inline-flex px-2 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">Uplata</span>
                                            @else
                                                <span class="inline-flex px-2 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-700">Isplata</span>
                                            @endif
                                        </td>
                                        <td class="px-4 py-3 whitespace-nowrap text-right text-gray-900">{{ $money($payment->amount) }}</td>
                                        <td class="px-4 py-3 whitespace-nowrap text-right">
                                            <a href="{{ route('transactions.edit', $payment) }}" class="text-indigo-600 hover:text-indigo-900">Detalji</a>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="px-4 py-6 text-center text-gray-500">
                                            Još nema uplata. <a href="{{ route('transactions.create') }}" class="text-indigo-600 hover:text-indigo-900">Evidentirajte prvu.</a>
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
