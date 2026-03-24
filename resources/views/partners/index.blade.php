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

            <!-- Add button -->
            <div class="mb-4 flex justify-end">
                <a href="{{ route('partners.create') }}">
                    <x-primary-link href="{{ route('partners.create') }}">
                        Dodaj novog partnera
                    </x-primary-link>
                </a>
            </div>

            <!-- Table -->
            <div class="bg-white shadow sm:rounded-lg overflow-hidden">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Ime</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Tip</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Telefon</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Napomena</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Akcije</th>
                        </tr>
                    </thead>

                    <tbody class="divide-y divide-gray-200">
                        @foreach($partners as $partner)
                        <tr>
                            <td class="px-6 py-4 text-sm text-gray-900">{{ $partner->name }}</td>
                            <td class="px-6 py-4 text-sm text-gray-900">{{ $partner->type }}</td>
                            <td class="px-6 py-4 text-sm text-gray-900">{{ $partner->phone }}</td>
                            <td class="px-6 py-4 text-sm text-gray-900">{{ $partner->note }}</td>

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
                            <td colspan="5" class="text-center py-6 text-gray-500">
                                Nema unetih partnera
                            </td>
                        </tr>
                        @endif
                    </tbody>
                </table>
            </div>

        </div>
    </div>
</x-app-layout>