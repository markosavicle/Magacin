<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Users
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            @if(session('status'))
                <div class="mb-4 p-4 bg-green-100 text-green-800 rounded">
                    {{ session('status') }}
                </div>
            @endif

            <div class="mb-8">
                <div class="flex justify-between items-center mb-2">
                    <h3 class="text-lg font-semibold">Svi korisnici</h3>
                    <!-- Toggle Filters Button -->
                    <button type="button" onclick="toggleUserFilters()" id="toggleUserFiltersBtn"
                            class="bg-gray-500 text-white px-4 py-2 rounded-md hover:bg-gray-600 flex items-center gap-2">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6V4m0 2a2 2 0 100 4m0-4a2 2 0 110 4m-6 8a2 2 0 100-4m0 4a2 2 0 100 4m0-4v2m0-6V4m6 6v10m6-2a2 2 0 100-4m0 4a2 2 0 100 4m0-4v2m0-6V4"></path>
                        </svg>
                        <span id="toggleUserFiltersText">Prikaži opcije za filtriranje</span>
                    </button>
                </div>
                <!-- Search, Filter, Sort -->
                <form method="GET" action="{{ route('users.index') }}" id="userFiltersSection" class="mb-4 grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 hidden">
                    <div>
                        <label for="search" class="block text-sm font-medium text-gray-700">Pretraga</label>
                        <input type="text" name="search" id="search" value="{{ request('search') }}" placeholder="Pretraži po imenu ili emailu" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                    </div>
                    <div>
                        <label for="approved" class="block text-sm font-medium text-gray-700">Odobren</label>
                        <select name="approved" id="approved" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                            <option value="">Svi</option>
                            <option value="1" @if(request('approved')==='1') selected @endif>Da</option>
                            <option value="0" @if(request('approved')==='0') selected @endif>Ne</option>
                        </select>
                    </div>
                    <div>
                        <label for="is_admin" class="block text-sm font-medium text-gray-700">Admin</label>
                        <select name="is_admin" id="is_admin" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                            <option value="">Svi</option>
                            <option value="1" @if(request('is_admin')==='1') selected @endif>Da</option>
                            <option value="0" @if(request('is_admin')==='0') selected @endif>Ne</option>
                        </select>
                    </div>
                    <div class="flex items-end gap-2">
                        <button type="submit" class="bg-gray-800 text-white px-4 py-2 rounded-md hover:bg-gray-700 font-semibold text-xs uppercase tracking-widest">Pretraži</button>
                        <a href="{{ route('users.index') }}" class="bg-red-600 text-white px-4 py-2 rounded-md hover:bg-red-500 font-semibold text-xs uppercase tracking-widest">Resetuj</a>
                    </div>
                </form>

                <script>
                function toggleUserFilters() {
                    const section = document.getElementById('userFiltersSection');
                    const btn = document.getElementById('toggleUserFiltersBtn');
                    const text = document.getElementById('toggleUserFiltersText');
                    if (section.classList.contains('hidden')) {
                        section.classList.remove('hidden');
                        text.textContent = 'Sakrij opcije za filtriranje';
                    } else {
                        section.classList.add('hidden');
                        text.textContent = 'Prikaži opcije za filtriranje';
                    }
                }
                </script>

                <div class="overflow-x-auto">
                    <table class="min-w-full bg-white border border-gray-200 rounded">
                        <thead>
                            <tr>
                                <th class="px-4 py-2 border">
                                    <a href="{{ route('users.index', array_merge(request()->query(), ['sort_by' => 'name', 'sort_direction' => request('sort_by') === 'name' && request('sort_direction') === 'asc' ? 'desc' : 'asc'])) }}" class="hover:text-gray-700 flex items-center gap-1">
                                        Ime
                                        @if(request('sort_by') === 'name')
                                            <span class="text-xs">{{ request('sort_direction', 'asc') === 'asc' ? '↑' : '↓' }}</span>
                                        @endif
                                    </a>
                                </th>
                                <th class="px-4 py-2 border">
                                    <a href="{{ route('users.index', array_merge(request()->query(), ['sort_by' => 'email', 'sort_direction' => request('sort_by') === 'email' && request('sort_direction') === 'asc' ? 'desc' : 'asc'])) }}" class="hover:text-gray-700 flex items-center gap-1">
                                        Email
                                        @if(request('sort_by') === 'email')
                                            <span class="text-xs">{{ request('sort_direction', 'asc') === 'asc' ? '↑' : '↓' }}</span>
                                        @endif
                                    </a>
                                </th>
                                <th class="px-4 py-2 border text-center">Odobren</th>
                                <th class="px-4 py-2 border text-center">Admin</th>
                                <th class="px-4 py-2 border text-center">Akcije</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($users as $user)
                                <tr>
                                    <td class="px-4 py-2 border">{{ $user->name }}</td>
                                    <td class="px-4 py-2 border">{{ $user->email }}</td>
                                    <td class="px-4 py-2 border text-center">
                                        @if($user->approved)
                                            <span class="px-2 py-1 text-xs font-semibold bg-green-100 text-green-700 rounded">Da</span>
                                        @else
                                            <span class="px-2 py-1 text-xs font-semibold bg-red-100 text-red-700 rounded">Ne</span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-2 border text-center">
                                        @if($user->is_admin)
                                            <span class="px-2 py-1 text-xs font-semibold bg-green-100 text-green-700 rounded">Da</span>
                                        @else
                                            <span class="px-2 py-1 text-xs font-semibold bg-red-100 text-red-700 rounded">Ne</span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-2 border text-center">
                                        @if(!$user->is_admin)
                                        <div class="flex justify-center">
                                            <form action="{{ route('users.destroy', $user) }}" method="POST">
                                                @csrf
                                                @method('DELETE')
                                                <x-danger-button onclick="return confirm('Da li ste sigurni?')">
                                                    Obriši
                                                </x-danger-button>
                                            </form>
                                        </div>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="px-4 py-2 border text-center">Nema korisnika.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <div>
                <h3 class="text-lg font-semibold mb-2">Korisnici na čekanju</h3>
                <div class="overflow-x-auto">
                    <table class="min-w-full bg-white border border-gray-200 rounded">
                        <thead>
                            <tr>
                                <th class="px-4 py-2 border">Ime</th>
                                <th class="px-4 py-2 border">Email</th>
                                <th class="px-4 py-2 border text-center">Akcije</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($pendingUsers as $user)
                                <tr>
                                    <td class="px-4 py-2 border">{{ $user->name }}</td>
                                    <td class="px-4 py-2 border">{{ $user->email }}</td>
                                    <td class="px-4 py-2 border text-center">
                                        <div class="flex justify-center">
                                            <form action="{{ route('users.approve', $user) }}" method="POST">
                                                @csrf
                                                <x-success-button style="min-width:80px;background-color:#16a34a;color:#fff;">
                                                    Odobri
                                                </x-success-button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="3" class="px-4 py-2 border text-center">Nema korisnika na čekanju.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
