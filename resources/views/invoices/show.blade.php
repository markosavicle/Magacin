<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Faktura: {{ $invoice->invoice_number }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">

            <!-- FACTURA CARD -->
            <div class="bg-white shadow sm:rounded-lg p-6">
                <h3 class="text-lg font-semibold text-gray-700 mb-4">Podaci o fakturi</h3>

                <div class="grid grid-cols-2 gap-4 text-gray-800">
                    <div>
                        <strong>Broj fakture:</strong> {{ $invoice->invoice_number }}
                    </div>
                    <div>
                        <strong>Partner:</strong> 
                        <a href="{{ route('partners.show', $invoice->partner) }}" 
                           class="text-indigo-600 hover:text-indigo-900 font-semibold">
                            {{ $invoice->partner->name }}
                        </a>
                    </div>
                    <div>
                        <strong>Datum:</strong> {{ \Carbon\Carbon::parse($invoice->date)->format('d.m.Y') }}
                    </div>
                    <div>
                        <strong>Tip:</strong> {{ $invoice->type === 'out' ? 'Faktura (izlazna)' : 'Faktura (ulazna)' }}
                    </div>
                    <div>
                        <strong>Iznos:</strong> {{ number_format($invoice->amount, 2) }} RSD
                    </div>
                    <div>
                        <strong>Status:</strong>
                        @if($invoice->status == \App\Models\Invoice::STATUS_PAID)
                            <span class="px-2 py-1 text-xs font-semibold bg-green-100 text-green-700 rounded">
                                Plaćeno
                            </span>
                        @else
                            <span class="px-2 py-1 text-xs font-semibold bg-red-100 text-red-700 rounded">
                                Neplaćeno
                            </span>
                        @endif
                    </div>
                    @if($invoice->note)
                    <div class="col-span-2">
                        <strong>Napomena:</strong> {{ $invoice->note }}
                    </div>
                    @endif
                </div>
            </div>


            <!-- Invoice Items Table -->
            <div class="bg-white shadow sm:rounded-lg p-6">
                <h3 class="text-lg font-semibold text-gray-700 mb-4">Stavke fakture</h3>
                @if($invoice->items->count())
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Naziv</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Količina</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Cena</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Ukupno</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200">
                            @foreach($invoice->items as $item)
                            <tr>
                                <td class="px-6 py-4 text-sm text-gray-900">{{ $item->name }}</td>
                                <td class="px-6 py-4 text-sm text-gray-900">{{ $item->quantity }}</td>
                                <td class="px-6 py-4 text-sm text-gray-900">{{ number_format($item->price, 2) }} RSD</td>
                                <td class="px-6 py-4 text-sm text-gray-900">{{ number_format($item->total, 2) }} RSD</td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                @else
                    <div class="text-gray-500">Nema stavki za ovu fakturu.</div>
                @endif
            </div>

            <!-- Opcije / dugmad -->
            <div class="flex gap-2">
                <x-primary-link href="{{ route('invoices.edit', $invoice) }}">
                    Izmeni
                </x-primary-link>
                <x-secondary-button onclick="window.location='{{ route('invoices.exportPdf', $invoice) }}'">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 inline-block mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" /></svg>
                    PDF
                </x-secondary-button>

                <form method="POST" action="{{ route('invoices.destroy', $invoice) }}">
                    @csrf
                    @method('DELETE')
                    <x-danger-button onclick="return confirm('Da li ste sigurni da želite obrisati ovu fakturu?')">
                        Obriši
                    </x-danger-button>
                </form>
            </div>

        </div>
    </div>
</x-app-layout>