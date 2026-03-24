<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Dodaj novog partnera
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white shadow-sm sm:rounded-lg p-6">
                <form action="{{ route('partners.store') }}" method="POST">
                    @csrf
                    <div class="mb-3">
                        <label class="block font-medium text-gray-700">Ime partnera</label>
                        <input type="text" name="name" class="form-input mt-1 block w-full" required>
                    </div>

                    <div class="mb-3">
                        <label class="block font-medium text-gray-700">Tip</label>
                        <select name="type" class="form-select mt-1 block w-full">
                            <option value="">-- izaberi --</option>
                            <option value="kupac">Kupac</option>
                            <option value="dobavljac">Dobavljač</option>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="block font-medium text-gray-700">Telefon</label>
                        <input type="text" name="phone" class="form-input mt-1 block w-full">
                    </div>

                    <div class="mb-3">
                        <label class="block font-medium text-gray-700">Napomena</label>
                        <textarea name="note" class="form-textarea mt-1 block w-full"></textarea>
                    </div>

                    <button type="submit" class="px-4 py-2 bg-green-600 text-white rounded hover:bg-green-700">
                         Sačuvaj
                    </button>
                    <a href="{{ route('partners.index') }}" class="ml-2 inline-flex items-center px-4 py-2 bg-gray-500 border border-transparent 
                        rounded-md font-semibold text-white hover:bg-gray-600 focus:outline-none focus:ring-2 
                        focus:ring-offset-2 focus:ring-gray-400">
                        Otkaži
                    </a>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>