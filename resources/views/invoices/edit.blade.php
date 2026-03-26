<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Izmeni fakturu
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

                <form method="POST" action="{{ route('invoices.update', $invoice) }}">
                    @csrf
                    @method('PUT')

                    <!-- Partner -->
                    <div class="mb-4">
                        <x-input-label for="partner_id" value="Partner" />
                        <select name="partner_id" id="partner_id" class="block w-full mt-1 rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring focus:ring-indigo-200">
                            <option value="">-- izaberi partnera --</option>
                            @foreach($partners as $partner)
                                <option value="{{ $partner->id }}" {{ old('partner_id', $invoice->partner_id) == $partner->id ? 'selected' : '' }}>
                                    {{ $partner->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Invoice Number -->
                    <div class="mb-4">
                        <x-input-label for="invoice_number" value="Broj fakture" />
                        <x-text-input 
                            id="invoice_number" 
                            class="block w-full mt-1 bg-gray-100 cursor-not-allowed" 
                            type="text" 
                            name="invoice_number" 
                            value="{{ old('invoice_number', $invoice->invoice_number) }}" 
                            readonly
                        />
                    </div>

                    <!-- Date -->
                    <div class="mb-4">
                        <x-input-label for="date" value="Datum" />
                        <x-text-input id="date" class="block w-full mt-1" type="date" name="date" value="{{ old('date', $invoice->date->format('Y-m-d')) }}" required />
                    </div>

                    <!-- Type -->
                    <div class="mb-4">
                        <x-input-label for="type" value="Tip" />
                        <select name="type" id="type" class="block w-full mt-1 rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring focus:ring-indigo-200">
                            <option value="out" {{ old('type', $invoice->type) == 'out' ? 'selected' : '' }}>Faktura (izlazna)</option>
                            <option value="in" {{ old('type', $invoice->type) == 'in' ? 'selected' : '' }}>Faktura (ulazna)</option>
                        </select>
                    </div>

                    <!-- Status -->
                    <div class="mb-4">
                        <x-input-label for="status" value="Status" />
                        <select name="status" id="status" class="block w-full mt-1 rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring focus:ring-indigo-200">
                            <option value="{{ \App\Models\Invoice::STATUS_UNPAID }}" {{ old('status', $invoice->status) == \App\Models\Invoice::STATUS_UNPAID ? 'selected' : '' }}>Neplaćeno</option>
                            <option value="{{ \App\Models\Invoice::STATUS_PAID }}" {{ old('status', $invoice->status) == \App\Models\Invoice::STATUS_PAID ? 'selected' : '' }}>Plaćeno</option>
                        </select>
                    </div>

                    <!-- Note -->
                    <div class="mb-4">
                        <x-input-label for="note" value="Napomena" />
                        <textarea name="note" id="note" class="block w-full mt-1 rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring focus:ring-indigo-200">{{ old('note', $invoice->note) }}</textarea>
                    </div>

                    <div class="mb-4">
                        <h3 class="font-semibold mb-2">Stavke fakture</h3>

                        <div id="items">
                            @foreach($invoice->items as $i => $item)
                                <div class="grid grid-cols-4 gap-2 mb-2">
                                    <input type="hidden" name="items[{{ $i }}][id]" value="{{ $item->id }}">

                                    <input type="text" name="items[{{ $i }}][name]" value="{{ $item->name }}" class="border rounded p-2">
                                    <input type="number" step="0.01" name="items[{{ $i }}][quantity]" value="{{ $item->quantity }}" class="border rounded p-2">
                                    <input type="number" step="0.01" name="items[{{ $i }}][price]" value="{{ $item->price }}" class="border rounded p-2">

                                    <button type="button" onclick="removeItem(this)" class="bg-red-500 text-white px-2 rounded">X</button>
                                </div>
                            @endforeach
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
    let index = {{ $invoice->items->count() }};

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