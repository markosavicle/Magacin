<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ $partner->name }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">

            <!-- BALANCE CARD -->
            <div class="bg-white shadow sm:rounded-lg p-6">
                <h3 class="text-lg font-semibold text-gray-700 mb-2">Stanje</h3>

                <div class="text-2xl font-bold 
                    {{ $balance > 0 ? 'text-green-600' : ($balance < 0 ? 'text-red-600' : 'text-gray-700') }}">
                    {{ number_format($balance, 2) }} RSD
                </div>

                <p class="text-sm text-gray-500 mt-1">
                    {{ $balance > 0 ? 'Partner duguje vama' : ($balance < 0 ? 'Vi dugujete partneru' : 'Nema dugovanja') }}
                </p>
            </div>

            <!-- FAKTURE -->
            <div class="bg-white shadow sm:rounded-lg overflow-x-auto">
                <div class="p-6 border-b">
                    <h3 class="text-lg font-semibold text-gray-700">Fakture</h3>
                </div>

                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            @php
                                function getInvoiceSortUrl($partner, $column) {
                                    $currentSort = request('invoice_sort_by');
                                    $currentDirection = request('invoice_sort_direction', 'desc');

                                    if ($currentSort == $column) {
                                        $newDirection = $currentDirection == 'asc' ? 'desc' : 'asc';
                                    } else {
                                        $newDirection = 'desc'; // Default to desc for invoices
                                    }

                                    return route('partners.show', array_merge([$partner], request()->query(), [
                                        'invoice_sort_by' => $column,
                                        'invoice_sort_direction' => $newDirection
                                    ]));
                                }

                                function getInvoiceSortArrow($column) {
                                    $currentSort = request('invoice_sort_by');
                                    $currentDirection = request('invoice_sort_direction', 'desc');

                                    if ($currentSort == $column) {
                                        return $currentDirection == 'asc' ? '↑' : '↓';
                                    }
                                    return '';
                                }
                            @endphp
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">
                                <a href="{{ getInvoiceSortUrl($partner, 'invoice_number') }}" class="hover:text-gray-700 flex items-center gap-1">
                                    Broj fakture
                                    <span class="text-xs">{{ getInvoiceSortArrow('invoice_number') }}</span>
                                </a>
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">
                                <a href="{{ getInvoiceSortUrl($partner, 'type') }}" class="hover:text-gray-700 flex items-center gap-1">
                                    Tip
                                    <span class="text-xs">{{ getInvoiceSortArrow('type') }}</span>
                                </a>
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">
                                <a href="{{ getInvoiceSortUrl($partner, 'amount') }}" class="hover:text-gray-700 flex items-center gap-1">
                                    Iznos
                                    <span class="text-xs">{{ getInvoiceSortArrow('amount') }}</span>
                                </a>
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">
                                <a href="{{ getInvoiceSortUrl($partner, 'status') }}" class="hover:text-gray-700 flex items-center gap-1">
                                    Status
                                    <span class="text-xs">{{ getInvoiceSortArrow('status') }}</span>
                                </a>
                            </th>
                        </tr>
                    </thead>

                    <tbody class="divide-y divide-gray-200">
                        @forelse($invoices as $invoice)
                            <tr class="hover:bg-gray-50">
                                <td class="px-6 py-4 text-sm text-gray-900">
                                <a href="{{ route('invoices.show', $invoice) }}" class="text-indigo-600 hover:text-indigo-900 font-semibold">
                                    {{ $invoice->invoice_number }}
                                </a>
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-900">
                                    {{ $invoice->type == 'out'? 'Faktura (izlazna)' : 'Faktura (ulazna)' }}
                                </td>

                                <td class="px-6 py-4 text-sm text-gray-900">
                                    {{ number_format($invoice->amount, 2) }} RSD
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
                            </tr>
                        @empty
                            <tr>
                                <td colspan="3" class="text-center py-6 text-gray-500">
                                    Nema faktura
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <!-- TRANSAKCIJE -->
            <div class="bg-white shadow sm:rounded-lg overflow-x-auto">
                <div class="p-6 border-b">
                    <h3 class="text-lg font-semibold text-gray-700">Transakcije</h3>
                </div>

                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            @php
                                function getTransactionSortUrl($partner, $column) {
                                    $currentSort = request('transaction_sort_by');
                                    $currentDirection = request('transaction_sort_direction', 'desc');

                                    if ($currentSort == $column) {
                                        $newDirection = $currentDirection == 'asc' ? 'desc' : 'asc';
                                    } else {
                                        $newDirection = 'desc'; // Default to desc for transactions
                                    }

                                    return route('partners.show', array_merge([$partner], request()->query(), [
                                        'transaction_sort_by' => $column,
                                        'transaction_sort_direction' => $newDirection
                                    ]));
                                }

                                function getTransactionSortArrow($column) {
                                    $currentSort = request('transaction_sort_by');
                                    $currentDirection = request('transaction_sort_direction', 'desc');

                                    if ($currentSort == $column) {
                                        return $currentDirection == 'asc' ? '↑' : '↓';
                                    }
                                    return '';
                                }
                            @endphp
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">
                                <a href="{{ getTransactionSortUrl($partner, 'type') }}" class="hover:text-gray-700 flex items-center gap-1">
                                    Tip
                                    <span class="text-xs">{{ getTransactionSortArrow('type') }}</span>
                                </a>
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">
                                <a href="{{ getTransactionSortUrl($partner, 'amount') }}" class="hover:text-gray-700 flex items-center gap-1">
                                    Iznos
                                    <span class="text-xs">{{ getTransactionSortArrow('amount') }}</span>
                                </a>
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Napomena</th>
                        </tr>
                    </thead>

                    <tbody class="divide-y divide-gray-200">
                        @forelse($transactions as $t)
                            <tr class="hover:bg-gray-50">
                                <td class="px-6 py-4 text-sm text-gray-900">
                                    {{ $t->type }}
                                </td>

                                <td class="px-6 py-4 text-sm text-gray-900">
                                    {{ number_format($t->amount, 2) }} RSD
                                </td>

                                <td class="px-6 py-4 text-sm text-gray-900">
                                    {{ $t->note }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="3" class="text-center py-6 text-gray-500">
                                    Nema transakcija
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

        </div>
    </div>
</x-app-layout>