<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Dodaj novu fakturu
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white shadow sm:rounded-lg p-6">

                <!-- Validation errors -->
                @if ($errors->any())
                    <div class="mb-4 p-4 bg-red-100 text-red-800 rounded">
                        <ul class="list-disc list-inside">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <form method="POST" action="{{ route('invoices.store') }}">
                    @csrf

                    <!-- Partner -->
                    <div class="mb-4">
                        <x-input-label for="partner_id" value="Partner" />
                        <select name="partner_id" id="partner_id" class="block w-full mt-1 rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring focus:ring-indigo-200">
                            <option value="">-- izaberi partnera --</option>
                            @foreach($partners as $partner)
                                <option value="{{ $partner->id }}" {{ old('partner_id') == $partner->id ? 'selected' : '' }}>
                                    {{ $partner->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Invoice Number -->
                    <div class="mb-4">
                        <x-text-input id="invoice_number" class="block w-full mt-1" type="hidden" name="invoice_number" value="" required />
                    </div>

                    <!-- Date -->
                    <div class="mb-4">
                        <x-input-label for="date" value="Datum" />
                        <x-text-input id="date" class="block w-full mt-1" type="date" name="date" value="{{ old('date', \Carbon\Carbon::today()->format('Y-m-d')) }}" required />
                    </div>

                    <!-- Amount -->
                    <!-- we don't need this, it will be calculated from items -->
                    {{-- <div class="mb-4">
                        <x-input-label for="amount" value="Iznos" />
                        <x-text-input id="amount" class="block w-full mt-1" type="number" step="0.01" name="amount" value="{{ old('amount') }}" required />
                    </div> --}}

                    <!-- Status -->
                    <div class="mb-4">
                        <x-input-label for="status" value="Status" />
                        <select name="status" id="status" class="block w-full mt-1 rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring focus:ring-indigo-200">
                            <option value="{{ \App\Models\Invoice::STATUS_UNPAID }}" {{ old('status') == \App\Models\Invoice::STATUS_UNPAID ? 'selected' : '' }}>Neplaćeno</option>
                            <option value="{{ \App\Models\Invoice::STATUS_PAID }}" {{ old('status') == \App\Models\Invoice::STATUS_PAID ? 'selected' : '' }}>Plaćeno</option>
                        </select>
                    </div>

                    <!-- Note -->
                    <div class="mb-4">
                        <x-input-label for="note" value="Napomena" />
                        <textarea name="note" id="note" class="block w-full mt-1 rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring focus:ring-indigo-200">{{ old('note') }}</textarea>
                    </div>

                    <!-- Invoice Items -->
                    <div class="mb-4">
                        <h3 class="font-semibold mb-2">Stavke fakture</h3>

                        <div id="items">
                            <div class="grid grid-cols-4 gap-2 mb-2">
                                <input type="text" name="items[0][name]" placeholder="Naziv" class="border rounded p-2">
                                <input type="number" step="0.01" name="items[0][quantity]" placeholder="Količina" class="border rounded p-2">
                                <input type="number" step="0.01" name="items[0][price]" placeholder="Cena" class="border rounded p-2">
                                <button type="button" onclick="removeItem(this)" class="bg-red-500 text-white px-2 rounded">X</button>
                            </div>
                        </div>

                        <button type="button" onclick="addItem()" class="bg-blue-500 text-white px-4 py-2 rounded">
                            + Dodaj stavku
                        </button>
                    </div>

                    <!-- Actions -->
                    <div class="flex items-center gap-3">
                        <x-primary-button type="submit">Sačuvaj</x-primary-button>
                        <x-danger-link href="{{ route('invoices.index') }}">Otkaži</x-danger-link>
                    </div>

                </form>
            </div>
        </div>
    </div>
</x-app-layout>

<script>
let index = 1;

function addItem() {
    let html = `
    <div class="grid grid-cols-4 gap-2 mb-2">
        <input type="text" name="items[${index}][name]" placeholder="Naziv" class="border rounded p-2">
        <input type="number" step="0.01" name="items[${index}][quantity]" placeholder="Količina" class="border rounded p-2">
        <input type="number" step="0.01" name="items[${index}][price]" placeholder="Cena" class="border rounded p-2">
        <button type="button" onclick="removeItem(this)" class="bg-red-500 text-white px-2 rounded">X</button>
    </div>
    `;
    document.getElementById('items').insertAdjacentHTML('beforeend', html);
    index++;
}

function removeItem(btn) {
    btn.parentElement.remove();
}
</script>