<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Partneri</h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">

            <!-- Dugme Dodaj -->
            <div class="mb-4">
                <a href="{{ route('partners.create') }}" 
                   class="inline-block px-4 py-2 bg-blue-600 text-white font-semibold rounded-lg shadow hover:bg-blue-700">
                    Dodaj novog partnera
                </a>
            </div>

            <!-- Tabela partnera -->
            <div class="overflow-x-auto">
                <table class="min-w-full bg-white border border-gray-200 rounded-lg shadow-sm">
                    <thead class="bg-gray-100 border-b border-gray-200">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider border-r border-gray-200">Ime</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider border-r border-gray-200">Tip</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider border-r border-gray-200">Telefon</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider border-r border-gray-200">Napomena</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Akcije</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        @foreach($partners as $partner)
                        <tr class="hover:bg-gray-50">
                            <td class="px-6 py-4 text-sm text-gray-900 border-r border-gray-200">{{ $partner->name }}</td>
                            <td class="px-6 py-4 text-sm text-gray-900 border-r border-gray-200">{{ $partner->type }}</td>
                            <td class="px-6 py-4 text-sm text-gray-900 border-r border-gray-200">{{ $partner->phone }}</td>
                            <td class="px-6 py-4 text-sm text-gray-900 border-r border-gray-200">{{ $partner->note }}</td>
                            <td class="px-6 py-4 text-sm space-x-2">
                                <a href="{{ route('partners.edit', $partner->id) }}" 
                                class="px-2 py-1 bg-yellow-500 text-white rounded hover:bg-yellow-600 text-xs">
                                Izmeni
                                </a>
                                <form action="{{ route('partners.destroy', $partner->id) }}" method="POST" class="inline">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" 
                                            class="px-2 py-1 bg-red-600 text-white rounded hover:bg-red-700 text-xs"
                                            onclick="return confirm('Da li ste sigurni?')">
                                        Obriši
                                    </button>
                                </form>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

        </div>
    </div>
</x-app-layout>