<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Fakture
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">

            @if(session('success'))
                <div class="mb-4 p-4 bg-green-100 text-green-800 rounded">
                    {{ session('success') }}
                </div>
            @endif

            <div class="mb-4 flex justify-end">
                <x-primary-link href="{{ route('invoices.create') }}">
                    Dodaj novu fakturu
                </x-primary-link>
            </div>

            <div class="bg-white shadow sm:rounded-lg overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Broj fakture</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Partner</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Datum</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Iznos</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Tip</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Napomena</th>
                            {{-- nema potrebe za stavkama, vide se u edit faktura --}}
                            {{-- <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Stavke</th> --}}
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Akcije</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        @foreach($invoices as $invoice)
                        <tr>
                            <td class="px-6 py-4 text-sm text-gray-900">
                                <a href="{{ route('invoices.show', $invoice) }}" class="text-indigo-600 hover:text-indigo-900 font-semibold">
                                    {{ $invoice->invoice_number }}
                                </a>
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-900">
                                <a href="{{ route('partners.show', $invoice->partner) }}" 
                                class="text-indigo-600 hover:text-indigo-900 font-semibold">
                                    {{ $invoice->partner->name }}
                                </a>
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-900">{{ $invoice->date->format('d.m.Y') }}</td>
                            <td class="px-6 py-4 text-sm text-gray-900">{{ number_format($invoice->amount, 2) }} RSD</td>
                            <td class="px-6 py-4 text-sm text-gray-900">
                                {{ $invoice->type == 'out'? 'Faktura (izlazna)' : 'Faktura (ulazna)' }}
                            </td>
                            <td class="px-6 py-4 text-sm">
                                    @if($invoice->status == \App\Models\Invoice::STATUS_PAID)
                                        <span class="px-2 py-1 text-xs font-semibold bg-green-100 text-green-700 rounded">
                                            Plaćeno
                                        </span>
                                    @else
                                        <span class="px-2 py-1 text-xs font-semibold bg-red-100 text-red-700 rounded">
                                            Neplaćeno
                                        </span>
                                    @endif
                                </td>
                            <td class="px-6 py-4 text-sm text-gray-900">{{ $invoice->note }}</td>
                            {{-- <td class="px-6 py-4 text-sm text-gray-900">
                                @foreach($invoice->items as $item)
                                    <div>
                                        {{ $item->name }} ({{ $item->quantity }}KG x {{ $item->price }}RSD)
                                    </div>
                                @endforeach
                            </td> --}}
                            <td class="px-6 py-4 text-sm flex gap-2">
                                <!-- Edit -->
                                <x-primary-link href="{{ route('invoices.edit', $invoice) }}">
                                    Izmeni
                                </x-primary-link>

                                <!-- Delete -->
                                <form method="POST" action="{{ route('invoices.destroy', $invoice) }}">
                                    @csrf
                                    @method('DELETE')

                                    <x-danger-button onclick="return confirm('Da li ste sigurni?')">
                                        Obriši
                                    </x-danger-button>
                                </form>
                            </td>
                        </tr>
                        @endforeach

                        @if($invoices->isEmpty())
                        <tr>
                            <td colspan="7" class="text-center py-6 text-gray-500">
                                Nema unetih faktura
                            </td>
                        </tr>
                        @endif
                    </tbody>
                </table>
            </div>

            <div class="mt-4">
                {{ $invoices->links() }}
            </div>
        </div>
    </div>
</x-app-layout>