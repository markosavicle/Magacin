<x-app-layout>
<div class="py-12">
    <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">

        <!-- Toggle Filters Button -->
        <div class="mb-4 flex justify-end">
            <button type="button" onclick="toggleFilters()" id="toggleFiltersBtn"
                    class="bg-gray-500 text-white px-4 py-2 rounded-md hover:bg-gray-600 flex items-center gap-2">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6V4m0 2a2 2 0 100 4m0-4a2 2 0 110 4m-6 8a2 2 0 100-4m0 4a2 2 0 100 4m0-4v2m0-6V4m6 6v10m6-2a2 2 0 100-4m0 4a2 2 0 100 4m0-4v2m0-6V4"></path>
                </svg>
                <span id="toggleText">Prikaži opcije za sortiranje</span>
            </button>
        </div>

        <!-- Search and Filters (Hidden by default) -->
        <div id="filtersSection" class="mb-4 bg-white shadow sm:rounded-lg p-4 hidden">
            <form method="GET" action="{{ route('history.index') }}" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4">
                <!-- Search -->
                <div class="md:col-span-2 lg:col-span-3 xl:col-span-4">
                    <label for="search" class="block text-sm font-medium text-gray-700">Pretraga</label>
                    <input type="text" name="search" id="search" value="{{ request('search') }}"
                           placeholder="Pretraži po opisu, akciji, korisniku..."
                           class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                </div>

                <!-- Action Filter -->
                <div>
                    <label for="action" class="block text-sm font-medium text-gray-700">Akcija</label>
                    <select name="action" id="action" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                        <option value="">Sve akcije</option>
                        <option value="created" {{ request('action') == 'created' ? 'selected' : '' }}>Kreirano</option>
                        <option value="updated" {{ request('action') == 'updated' ? 'selected' : '' }}>Izmenjeno</option>
                        <option value="deleted" {{ request('action') == 'deleted' ? 'selected' : '' }}>Obrisano</option>
                    </select>
                </div>

                <!-- Sort By -->
                <div>
                    <label for="sort_by" class="block text-sm font-medium text-gray-700">Sortiraj po</label>
                    <select name="sort_by" id="sort_by" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                        <option value="created_at" {{ request('sort_by', 'created_at') == 'created_at' ? 'selected' : '' }}>Datum</option>
                        <option value="action" {{ request('sort_by') == 'action' ? 'selected' : '' }}>Akcija</option>
                        <option value="description" {{ request('sort_by') == 'description' ? 'selected' : '' }}>Opis</option>
                    </select>
                </div>

                <!-- Sort Direction -->
                <div>
                    <label for="sort_direction" class="block text-sm font-medium text-gray-700">Redosled</label>
                    <select name="sort_direction" id="sort_direction" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                        <option value="desc" {{ request('sort_direction', 'desc') == 'desc' ? 'selected' : '' }}>Opadajuće</option>
                        <option value="asc" {{ request('sort_direction') == 'asc' ? 'selected' : '' }}>Rastuće</option>
                    </select>
                </div>

                <!-- Buttons -->
                <div class="md:col-span-2 lg:col-span-3 xl:col-span-4 flex gap-2">
                    <button type="submit" class="bg-gray-800 text-white px-4 py-2 rounded-md hover:bg-gray-700 font-semibold text-xs uppercase tracking-widest">
                        Pretraži
                    </button>
                    <a href="{{ route('history.index') }}" class="bg-red-600 text-white px-4 py-2 rounded-md hover:bg-red-500 font-semibold text-xs uppercase tracking-widest">
                        Resetuj
                    </a>
                </div>
            </form>
        </div>

        <div class="bg-white overflow-hidden shadow-xl sm:rounded-lg p-6">
            <h2 class="text-2xl font-bold mb-6">Istorija izmena</h2>

            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Akcija</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Opis</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Korisnik</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Datum</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        @forelse($logs as $log)
                            <tr class="hover:bg-gray-50">
                                <td class="px-6 py-4 text-sm text-gray-900">
                                    @if($log->action == 'created')
                                        <span class="px-2 py-1 text-xs font-semibold bg-green-100 text-green-700 rounded">Kreirano</span>
                                    @elseif($log->action == 'updated')
                                        <span class="px-2 py-1 text-xs font-semibold bg-blue-100 text-blue-700 rounded">Izmenjeno</span>
                                    @elseif($log->action == 'deleted')
                                        <span class="px-2 py-1 text-xs font-semibold bg-red-100 text-red-700 rounded">Obrisano</span>
                                    @endif
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-900">
                                    @php
                                        $desc = $log->description;
                                        $linkedDesc = null;
                                        if ($log->model_type === \App\Models\Partner::class) {
                                            $url = route('partners.show', $log->model_id);
                                            if (preg_match('/^(Partner [^:]+: )(.+)$/u', $desc, $m)) {
                                                $linkedDesc = e($m[1]) . '<a href="' . $url . '" class="text-indigo-600 hover:text-indigo-900 font-medium">' . e($m[2]) . '</a>';
                                            }
                                        } elseif ($log->model_type === \App\Models\Invoice::class) {
                                            $url = route('invoices.show', $log->model_id);
                                            if (preg_match('/^(Faktura [^:]+: )(.+)$/u', $desc, $m)) {
                                                $linkedDesc = e($m[1]) . '<a href="' . $url . '" class="text-indigo-600 hover:text-indigo-900 font-medium">' . e($m[2]) . '</a>';
                                            }
                                        } elseif ($log->model_type === \App\Models\InvoiceItem::class) {
                                            $invoiceId = ($log->new_values['invoice_id'] ?? null) ?: ($log->old_values['invoice_id'] ?? null);
                                            if ($invoiceId && preg_match('/^(.+\(Faktura: )([^)]+)(\))$/u', $desc, $m)) {
                                                $url = route('invoices.show', $invoiceId);
                                                $linkedDesc = e($m[1]) . '<a href="' . $url . '" class="text-indigo-600 hover:text-indigo-900 font-medium">' . e($m[2]) . '</a>' . e($m[3]);
                                            }
                                        }
                                    @endphp
                                    @if($linkedDesc)
                                        {!! $linkedDesc !!}
                                    @else
                                        {{ $desc }}
                                    @endif
                                    @if($log->old_values || $log->new_values)
                                        <div class="mt-2 text-xs text-gray-500">
                                            @if($log->old_values)
                                                <div><strong>Staro:</strong> {{ implode(', ', array_map(fn($k, $v) => "$k: " . (is_array($v) ? json_encode($v) : $v), array_keys($log->old_values), $log->old_values)) }}</div>
                                            @endif
                                            @if($log->new_values)
                                                <div><strong>Novo:</strong> {{ implode(', ', array_map(fn($k, $v) => "$k: " . (is_array($v) ? json_encode($v) : $v), array_keys($log->new_values), $log->new_values)) }}</div>
                                            @endif
                                        </div>
                                    @endif
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-900">
                                    {{ $log->user ? $log->user->name : 'Sistem' }}
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-900">
                                    {{ $log->created_at->format('d.m.Y H:i') }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="text-center py-6 text-gray-500">Nema istorije</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            @if($logs->hasPages())
                <div class="px-6 py-4 border-t border-gray-200">
                    {{ $logs->appends(request()->query())->links() }}
                </div>
            @endif
        </div>
    </div>
</div>

<script>
    function toggleFilters() {
        const filtersSection = document.getElementById('filtersSection');
        const toggleText = document.getElementById('toggleText');

        if (filtersSection.classList.contains('hidden')) {
            filtersSection.classList.remove('hidden');
            toggleText.textContent = 'Sakrij opcije za sortiranje';
        } else {
            filtersSection.classList.add('hidden');
            toggleText.textContent = 'Prikaži opcije za sortiranje';
        }
    }
</script>
</x-app-layout>
