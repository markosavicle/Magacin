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

            <!-- Opcije / dugmad -->
            <div class="flex gap-2">
                <x-primary-link href="{{ route('invoices.edit', $invoice) }}">
                    Izmeni
                </x-primary-link>

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