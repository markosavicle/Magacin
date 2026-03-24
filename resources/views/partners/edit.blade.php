<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Izmeni partnera
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white shadow sm:rounded-lg p-6">

                <!-- Validation errors -->
               @if ($errors->any())
                        <div class="mb-4 p-4 bg-red-100 text-red-800 rounded">
                            <ul class="list-disc pl-5">
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif


                <form method="POST" action="{{ route('partners.update', $partner) }}">
                    @csrf
                    @method('PUT')

                    <!-- Name -->
                    <div class="mb-4">
                        <x-input-label for="name" value="Ime partnera" />
                        <x-text-input id="name" class="block mt-1 w-full" type="text" name="name"
                                      value="{{ old('name', $partner->name) }}" required autofocus />
                        <x-input-error :messages="$errors->get('name')" class="mt-2" />
                    </div>

                    <!-- Type -->
                    <div class="mb-4">
                        <x-input-label for="type" value="Tip" />
                        <select name="type" id="type"
                            class="border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm block w-full mt-1">
                            <option value="">-- izaberi --</option>
                            <option value="kupac" {{ old('type', $partner->type) == 'kupac' ? 'selected' : '' }}>Kupac</option>
                            <option value="dobavljac" {{ old('type', $partner->type) == 'dobavljac' ? 'selected' : '' }}>Dobavljač</option>
                        </select>
                    </div>

                    <!-- Phone -->
                    <div class="mb-4">
                        <x-input-label for="phone" value="Telefon" />
                        <x-text-input id="phone" class="block mt-1 w-full" type="text" name="phone"
                                      value="{{ old('phone', $partner->phone) }}" />
                    </div>

                    <!-- Note -->
                    <div class="mb-4">
                        <x-input-label for="note" value="Napomena" />
                        <textarea name="note" id="note"
                            class="border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm block w-full mt-1">{{ old('note', $partner->note) }}</textarea>
                    </div>

                    <!-- Actions -->
                    <div class="flex items-center gap-3">
                        <x-primary-button type="submit">
                            Sačuvaj
                        </x-primary-button>

                        <x-danger-link href="{{ route('partners.index') }}">
                            Otkaži
                        </x-danger-link>
                    </div>
                </form>

            </div>
        </div>
    </div>
</x-app-layout>