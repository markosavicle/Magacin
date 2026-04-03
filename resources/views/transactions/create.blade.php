<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Dodaj novu transakciju
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white shadow sm:rounded-lg p-6">

                @if ($errors->any())
                    <div class="mb-4 p-4 bg-red-100 text-red-800 rounded">
                        <ul class="list-disc list-inside">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <form method="POST" action="{{ route('transactions.store') }}">
                    @csrf

                    @if($selectedPartnerId)
                        <input type="hidden" name="redirect_to_partner" value="1">
                    @endif

                    <!-- Partner -->
                    <div class="mb-4">
                        <x-input-label for="partner_id" value="Partner" />
                        <select name="partner_id" id="partner_id" class="block w-full mt-1 rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring focus:ring-indigo-200" required>
                            <option value="">-- izaberi partnera --</option>
                            @foreach($partners as $partner)
                                <option value="{{ $partner->id }}" {{ old('partner_id', $selectedPartnerId) == $partner->id ? 'selected' : '' }}>
                                    {{ $partner->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Date -->
                    <div class="mb-4">
                        <x-input-label for="date" value="Datum" />
                        <x-text-input id="date" class="block w-full mt-1" type="date" name="date" value="{{ old('date', \Carbon\Carbon::today()->format('Y-m-d')) }}" required />
                    </div>

                    <!-- Type -->
                    <div class="mb-4">
                        <x-input-label for="type" value="Tip" />
                        <select name="type" id="type" class="block w-full mt-1 rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring focus:ring-indigo-200" required>
                            <option value="uplata" {{ old('type') == 'uplata' ? 'selected' : '' }}>Uplata</option>
                            <option value="isplata" {{ old('type') == 'isplata' ? 'selected' : '' }}>Isplata</option>
                        </select>
                    </div>

                    <!-- Amount -->
                    <div class="mb-4">
                        <x-input-label for="amount" value="Iznos (RSD)" />
                        <x-text-input id="amount" class="block w-full mt-1" type="number" step="0.01" min="0.01" name="amount" value="{{ old('amount') }}" required />
                    </div>

                    <!-- Note -->
                    <div class="mb-4">
                        <x-input-label for="note" value="Napomena" />
                        <textarea name="note" id="note" class="block w-full mt-1 rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring focus:ring-indigo-200">{{ old('note') }}</textarea>
                    </div>

                    <!-- Actions -->
                    <div class="flex items-center gap-3">
                        <x-primary-button type="submit">Sačuvaj</x-primary-button>
                        <x-danger-link href="{{ $selectedPartnerId ? route('partners.show', $selectedPartnerId) : route('transactions.index') }}">Otkaži</x-danger-link>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
