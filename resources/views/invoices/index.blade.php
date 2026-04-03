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

            <div class="mb-4 hidden sm:flex justify-between items-center">
                <x-primary-link href="{{ route('invoices.create') }}">
                    Dodaj novu fakturu
                </x-primary-link>
                <button type="button" onclick="toggleFilters()" id="toggleFiltersBtn"
                        class="bg-gray-500 hover:bg-gray-600 text-white rounded-md px-5 py-3 flex items-center gap-2 shadow transition-all duration-200 text-base font-semibold">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M8 12h8m-4 6h.01" />
                    </svg>
                    <span>Filteri</span>
                </button>
            </div>

            <!-- Mobile Filter Button (above table, inline) -->
            <div class="mb-4 sm:hidden flex items-center">
                <button type="button" onclick="toggleFilters()" id="toggleFiltersBtnMobile"
                    class="bg-gray-500 hover:bg-gray-600 text-white rounded-md px-5 py-3 flex items-center gap-2 shadow transition-all duration-200 text-base font-semibold">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M8 12h8m-4 6h.01" />
                    </svg>
                    <span>Filteri</span>
                </button>
            </div>
            <!-- Mobile Add Button (fixed bottom-right, proper circle) -->
            <a href="{{ route('invoices.create') }}" id="addInvoiceBtnMobile"
            class="sm:hidden fixed z-[9999] bg-indigo-600 hover:bg-indigo-700 text-white flex items-center justify-center shadow-lg"
            style="bottom: 24px; right: 24px; width: 56px; height: 56px; border-radius: 50%;">
                <svg class="w-7 h-7" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" />
                </svg>
            </a>

            <!-- Search and Filters (Hidden by default) -->
            <div id="filtersSection" class="mb-4 bg-white shadow sm:rounded-lg p-4 hidden">
                <form method="GET" action="{{ route('invoices.index') }}" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4">
                    <!-- Search -->
                    <div class="md:col-span-2 lg:col-span-3 xl:col-span-4">
                        <label for="search" class="block text-sm font-medium text-gray-700">Pretraga</label>
                        <input type="text" name="search" id="search" 
                               placeholder="Pretraži po broju fakture, partneru, napomeni..."
                               class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                    </div>

                    <!-- Type Filter -->
                    <div>
                        <label for="type" class="block text-sm font-medium text-gray-700">Tip</label>
                        <select name="type" id="type" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                            <option value="">Svi tipovi</option>
                            <option value="out">Izlazna</option>
                            <option value="in">Ulazna</option>
                        </select>
                    </div>

                    <!-- Partner Filter -->
                    <div>
                        <label for="partner_id" class="block text-sm font-medium text-gray-700">Partner</label>
                        <select name="partner_id" id="partner_id" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                            <option value="">Svi partneri</option>
                            @foreach($partners as $partnerOption)
                                <option value="{{ $partnerOption->id }}">
                                    {{ $partnerOption->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Date From -->
                    <div>
                        <label for="date_from" class="block text-sm font-medium text-gray-700">Datum od</label>
                        <input type="date" name="date_from" id="date_from" 
                               class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                    </div>

                    <!-- Date To -->
                    <div>
                        <label for="date_to" class="block text-sm font-medium text-gray-700">Datum do</label>
                        <input type="date" name="date_to" id="date_to" 
                               class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                    </div>

                    <!-- Buttons -->
                    <div class="md:col-span-2 lg:col-span-3 xl:col-span-4 flex gap-2">
                        <button type="submit" class="bg-gray-800 text-white px-4 py-2 rounded-md hover:bg-gray-700 font-semibold text-xs uppercase tracking-widest">
                            Pretraži
                        </button>
                        <a href="{{ route('invoices.index') }}" class="bg-red-600 text-white px-4 py-2 rounded-md hover:bg-red-500 font-semibold text-xs uppercase tracking-widest">
                            Resetuj
                        </a>
                    </div>
                </form>
            </div>

            <div class="bg-white shadow sm:rounded-lg overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            @php
                                function getSortUrl($column) {
                                    $currentSort = request('sort_by');
                                    $currentDirection = request('sort_direction', 'desc');

                                    if ($currentSort == $column) {
                                        $newDirection = $currentDirection == 'asc' ? 'desc' : 'asc';
                                    } else {
                                        $newDirection = 'desc'; // Default to desc for new columns
                                    }

                                    return route('invoices.index', array_merge(request()->query(), [
                                        'sort_by' => $column,
                                        'sort_direction' => $newDirection
                                    ]));
                                }

                                function getSortArrow($column) {
                                    $currentSort = request('sort_by');
                                    $currentDirection = request('sort_direction', 'desc');

                                    if ($currentSort == $column) {
                                        return $currentDirection == 'asc' ? '↑' : '↓';
                                    }
                                    return '';
                                }
                            @endphp
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">
                                <a href="{{ getSortUrl('invoice_number') }}" class="hover:text-gray-700 flex items-center gap-1">
                                    Broj fakture
                                    <span class="text-xs">{{ getSortArrow('invoice_number') }}</span>
                                </a>
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Partner</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">
                                <a href="{{ getSortUrl('date') }}" class="hover:text-gray-700 flex items-center gap-1">
                                    Datum
                                    <span class="text-xs">{{ getSortArrow('date') }}</span>
                                </a>
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">
                                <a href="{{ getSortUrl('amount') }}" class="hover:text-gray-700 flex items-center gap-1">
                                    Iznos
                                    <span class="text-xs">{{ getSortArrow('amount') }}</span>
                                </a>
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">
                                <a href="{{ getSortUrl('type') }}" class="hover:text-gray-700 flex items-center gap-1">
                                    Tip
                                    <span class="text-xs">{{ getSortArrow('type') }}</span>
                                </a>
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Napomena</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">
                                <a href="{{ getSortUrl('created_at') }}" class="hover:text-gray-700 flex items-center gap-1">
                                    Kreirano
                                    <span class="text-xs">{{ getSortArrow('created_at') }}</span>
                                </a>
                            </th>
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
                            <td class="px-6 py-4 text-sm text-gray-900">{{ $invoice->note }}</td>
                            <td class="px-6 py-4 text-sm text-gray-900">{{ $invoice->created_at->format('d.m.Y') }}</td>
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
                            <td colspan="8" class="text-center py-6 text-gray-500">
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

    <script>
        function toggleFilters() {
            const filtersSection = document.getElementById('filtersSection');
            const toggleText = document.getElementById('toggleText');

            if (filtersSection.classList.contains('hidden')) {
                filtersSection.classList.remove('hidden');
            } else {
                filtersSection.classList.add('hidden');
            }
        }
    </script>
</x-app-layout>