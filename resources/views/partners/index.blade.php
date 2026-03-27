<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Partneri
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">

            <!-- Success message -->
            @if(session('success'))
                <div class="mb-4 p-4 bg-green-100 text-green-800 rounded">
                    {{ session('success') }}
                </div>
            @endif

            <!-- Add button and Toggle Filters -->
            <div class="mb-4 flex justify-between items-center">
                <x-primary-link href="{{ route('partners.create') }}">
                    Dodaj novog partnera
                </x-primary-link>

                <!-- Toggle Filters Button -->
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
                <form method="GET" action="{{ route('partners.index') }}" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4">
                    <!-- Search -->
                    <div class="md:col-span-2 lg:col-span-3 xl:col-span-4">
                        <label for="search" class="block text-sm font-medium text-gray-700">Pretraga</label>
                        <input type="text" name="search" id="search" 
                               placeholder="Pretraži po imenu, tipu, email-u, telefonu..."
                               class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                    </div>

                    <!-- Type Filter -->
                    <div>
                        <label for="type" class="block text-sm font-medium text-gray-700">Tip</label>
                        <select name="type" id="type" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                            <option value="">Svi tipovi</option>
                            @php
                                $types = \App\Models\Partner::distinct()->pluck('type')->filter()->values();
                            @endphp
                            @foreach($types as $typeOption)
                                <option value="{{ $typeOption }}">
                                    {{ $typeOption }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <!-- City Filter -->
                    <div>
                        <label for="city" class="block text-sm font-medium text-gray-700">Grad</label>
                        <select name="city" id="city" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                            <option value="">Svi gradovi</option>
                            @php
                                $cities = \App\Models\Partner::distinct()->pluck('city')->filter()->values();
                            @endphp
                            @foreach($cities as $cityOption)
                                <option value="{{ $cityOption }}">
                                    {{ $cityOption }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Buttons -->
                    <div class="md:col-span-2 lg:col-span-3 xl:col-span-4 flex gap-2">
                        <button type="submit" class="bg-gray-800 text-white px-4 py-2 rounded-md hover:bg-gray-700 font-semibold text-xs uppercase tracking-widest">
                            Pretraži
                        </button>
                        <a href="{{ route('partners.index') }}" class="bg-red-600 text-white px-4 py-2 rounded-md hover:bg-red-500 font-semibold text-xs uppercase tracking-widest">
                            Resetuj
                        </a>
                    </div>
                </form>
            </div>

            <!-- Table -->
            <div class="bg-white shadow sm:rounded-lg overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            @php
                                function getSortUrl($column) {
                                    $currentSort = request('sort_by');
                                    $currentDirection = request('sort_direction', 'asc');

                                    if ($currentSort == $column) {
                                        $newDirection = $currentDirection == 'asc' ? 'desc' : 'asc';
                                    } else {
                                        $newDirection = 'asc'; // Default to asc for partners (alphabetical)
                                    }

                                    return route('partners.index', array_merge(request()->query(), [
                                        'sort_by' => $column,
                                        'sort_direction' => $newDirection
                                    ]));
                                }

                                function getSortArrow($column) {
                                    $currentSort = request('sort_by');
                                    $currentDirection = request('sort_direction', 'asc');

                                    if ($currentSort == $column) {
                                        return $currentDirection == 'asc' ? '↑' : '↓';
                                    }
                                    return '';
                                }
                            @endphp
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">
                                <a href="{{ getSortUrl('name') }}" class="hover:text-gray-700 flex items-center gap-1">
                                    Ime
                                    <span class="text-xs">{{ getSortArrow('name') }}</span>
                                </a>
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">
                                <a href="{{ getSortUrl('type') }}" class="hover:text-gray-700 flex items-center gap-1">
                                    Tip
                                    <span class="text-xs">{{ getSortArrow('type') }}</span>
                                </a>
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Telefon</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Napomena</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Email</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Adresa</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">
                                <a href="{{ getSortUrl('city') }}" class="hover:text-gray-700 flex items-center gap-1">
                                    Grad
                                    <span class="text-xs">{{ getSortArrow('city') }}</span>
                                </a>
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">PDV broj</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Kontakt osoba</th>
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
                        @foreach($partners as $partner)
                        <tr>
                            <td class="px-6 py-4 text-sm text-gray-900">
                                <a href="{{ route('partners.show', $partner) }}" 
                                class="text-indigo-600 hover:text-indigo-900 font-semibold">
                                    {{ $partner->name }}
                                </a>
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-900">{{ $partner->type }}</td>
                            <td class="px-6 py-4 text-sm text-gray-900">{{ $partner->phone }}</td>
                            <td class="px-6 py-4 text-sm text-gray-900">{{ $partner->note }}</td>
                            <td class="px-6 py-4 text-sm text-gray-900">{{ $partner->email }}</td>
                            <td class="px-6 py-4 text-sm text-gray-900">{{ $partner->address }}</td>
                            <td class="px-6 py-4 text-sm text-gray-900">{{ $partner->city }}</td>
                            <td class="px-6 py-4 text-sm text-gray-900">{{ $partner->vat_number }}</td>
                            <td class="px-6 py-4 text-sm text-gray-900">{{ $partner->contact_person }}</td>
                            <td class="px-6 py-4 text-sm text-gray-900">{{ $partner->created_at->format('d.m.Y') }}</td>

                            <td class="px-6 py-4 text-sm flex gap-2">
                                <!-- Edit -->
                                <x-primary-link href="{{ route('partners.edit', $partner) }}">
                                    Izmeni
                                </x-primary-link>

                                <!-- Delete -->
                                <form method="POST" action="{{ route('partners.destroy', $partner) }}">
                                    @csrf
                                    @method('DELETE')

                                    <x-danger-button onclick="return confirm('Da li ste sigurni?')">
                                        Obriši
                                    </x-danger-button>
                                </form>
                            </td>
                        </tr>
                        @endforeach

                        @if($partners->isEmpty())
                        <tr>
                            <td colspan="12" class="text-center py-6 text-gray-500">
                                Nema unetih partnera
                            </td>
                        </tr>
                        @endif
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <div class="mt-4">
                {{ $partners->links() }} <!-- Laravel-ova funkcija za paginaciju -->

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