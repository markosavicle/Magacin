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
                <div class="mt-4">
                    <x-secondary-button onclick="window.location='{{ route('partners.exportPdf', $partner) }}'">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 inline-block mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" /></svg>
                        PDF
                    </x-secondary-button>
                </div>
            </div>

            <!-- TABS -->
            <div class="bg-white shadow sm:rounded-lg">
                <!-- Tab Navigation -->
                <div class="border-b border-gray-200">
                    <nav class="-mb-px flex space-x-8 px-6" aria-label="Tabs">
                        <button onclick="showTab('fakture')" class="tab-button border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm" data-tab="fakture">
                            Fakture
                        </button>
                        <button onclick="showTab('transakcije')" class="tab-button border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm" data-tab="transakcije">
                            Transakcije
                        </button>
                    </nav>
                </div>

                <!-- Tab Content -->
                <div id="fakture-tab" class="tab-content">
                    <!-- FAKTURE -->
                    <div class="overflow-x-auto">
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
                                <td colspan="4" class="text-center py-6 text-gray-500">
                                    Nema faktura
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                        </table>
                    </div>
                </div>

                <!-- TRANSAKCIJE TAB -->
                <div id="transakcije-tab" class="tab-content hidden">
                    <!-- TRANSAKCIJE -->
                    <div class="overflow-x-auto">
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

                <!-- End of removed Istorija tab -->
            </div>

        </div>
    </div>

    <script>
        function showTab(tabName) {
            // Hide all tab contents
            document.querySelectorAll('.tab-content').forEach(content => {
                content.classList.add('hidden');
            });

            // Remove active class from all tab buttons
            document.querySelectorAll('.tab-button').forEach(button => {
                button.classList.remove('border-indigo-500', 'text-indigo-600');
                button.classList.add('border-transparent', 'text-gray-500');
            });

            // Show selected tab content
            document.getElementById(tabName + '-tab').classList.remove('hidden');

            // Add active class to selected tab button
            document.querySelector(`[data-tab="${tabName}"]`).classList.remove('border-transparent', 'text-gray-500');
            document.querySelector(`[data-tab="${tabName}"]`).classList.add('border-indigo-500', 'text-indigo-600');
        }

        // Show default tab (fakture) on page load
        document.addEventListener('DOMContentLoaded', function() {
            showTab('fakture');
        });
    </script>
</x-app-layout>