<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Dodaj novog partnera
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white shadow sm:rounded-lg p-6">

                <!-- Validation errors -->
                <x-auth-session-status class="mb-4" :status="session('status')" />

                <form method="POST" action="{{ route('partners.store') }}">
                    @csrf

                    <!-- Name -->
                    <div class="mb-4">
                        <x-input-label for="name" value="Ime partnera" />
                        <x-text-input id="name" class="block mt-1 w-full" type="text" name="name" required autofocus />
                        <x-input-error :messages="$errors->get('name')" class="mt-2" />
                    </div>

                    <!-- Type -->
                    <div class="mb-4">
                        <x-input-label for="type" value="Tip" />
                        <select name="type" id="type" class="border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm block w-full mt-1">
                            <option value="">-- izaberi --</option>
                            <option value="kupac">Kupac</option>
                            <option value="dobavljac">Dobavljač</option>
                        </select>
                    </div>

                    <!-- Phone -->
                    <div class="mb-4">
                        <x-input-label for="phone" value="Telefon" />
                        <x-text-input id="phone" class="block mt-1 w-full" type="text" name="phone" />
                    </div>

                    <!-- Note -->
                    <div class="mb-4">
                        <x-input-label for="note" value="Napomena" />
                        <textarea name="note" id="note"
                            class="border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm block w-full mt-1"></textarea>
                    </div>

                    <!-- Email -->
                    <div class="mb-4">
                        <x-input-label for="email" value="Email" />
                        <x-text-input id="email" class="block mt-1 w-full" type="email" name="email" value="{{ old('email', $partner->email ?? '') }}" />
                    </div>

                    <!-- Address -->
                    <div class="mb-4">
                        <x-input-label for="address" value="Adresa" />
                        <x-text-input id="address" class="block mt-1 w-full" type="text" name="address" value="{{ old('address', $partner->address ?? '') }}" />
                    </div>

                    <!-- City -->
                    <div class="mb-4">
                        <x-input-label for="city" value="Grad" />
                        <x-text-input id="city" class="block mt-1 w-full" type="text" name="city" value="{{ old('city', $partner->city ?? '') }}" />
                    </div>

                    <!-- Vat Number -->
                    <div class="mb-4">
                        <x-input-label for="vat_number" value="PDV broj" />
                        <x-text-input id="vat_number" class="block mt-1 w-full" type="text" name="vat_number" value="{{ old('vat_number', $partner->vat_number ?? '') }}" />
                    </div>

                    <!-- Contact Person -->
                    <div class="mb-4">
                        <x-input-label for="contact_person" value="Kontakt osoba" />
                        <x-text-input id="contact_person" class="block mt-1 w-full" type="text" name="contact_person" value="{{ old('contact_person', $partner->contact_person ?? '') }}" />
                    </div>


                    <!-- Actions -->
                    <div class="flex items-center gap-3">
                        <x-primary-button type="submit">
                            Sačuvaj
                        </x-primary-button>

                        <a href="{{ route('partners.index') }}">
                           <x-danger-link href="{{ route('partners.index') }}">
                                Otkaži
                            </x-danger-link>
                        </a>
                    </div>
                </form>

            </div>
        </div>
    </div>
</x-app-layout>