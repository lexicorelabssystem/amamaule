@csrf

<div class="grid grid-cols-1 md:grid-cols-2 gap-6">
    <!-- Names -->
    <div>
        <x-input-label for="legal_name" :value="__('Nombre legal')" />
        <x-text-input id="legal_name" name="legal_name" type="text" class="mt-1 block w-full" :value="old('legal_name', $artist->legal_name ?? '')" />
        <x-input-error class="mt-2" :messages="$errors->get('legal_name')" />
    </div>

    <div>
        <x-input-label for="public_name" :value="__('Nombre artístico público')" />
        <x-text-input id="public_name" name="public_name" type="text" class="mt-1 block w-full" :value="old('public_name', $artist->public_name ?? '')" />
        <x-input-error class="mt-2" :messages="$errors->get('public_name')" />
    </div>

    <div>
        <x-input-label for="artistic_name" :value="__('Alias artístico')" />
        <x-text-input id="artistic_name" name="artistic_name" type="text" class="mt-1 block w-full" :value="old('artistic_name', $artist->artistic_name ?? '')" />
        <x-input-error class="mt-2" :messages="$errors->get('artistic_name')" />
    </div>

    <div>
        <x-input-label for="document_number" :value="__('RUT / Documento')" />
        <x-text-input id="document_number" name="document_number" type="text" class="mt-1 block w-full" :value="old('document_number', $artist->document_number ?? '')" />
        <x-input-error class="mt-2" :messages="$errors->get('document_number')" />
    </div>

    <!-- Contact -->
    <div>
        <x-input-label for="email_contact" :value="__('Email de contacto')" />
        <x-text-input id="email_contact" name="email_contact" type="email" class="mt-1 block w-full" :value="old('email_contact', $artist->email_contact ?? '')" />
        <x-input-error class="mt-2" :messages="$errors->get('email_contact')" />
    </div>

    <div>
        <x-input-label for="phone" :value="__('Teléfono')" />
        <x-text-input id="phone" name="phone" type="text" class="mt-1 block w-full" :value="old('phone', $artist->phone ?? '')" />
        <x-input-error class="mt-2" :messages="$errors->get('phone')" />
    </div>

    <div>
        <x-input-label for="website" :value="__('Sitio web')" />
        <x-text-input id="website" name="website" type="url" class="mt-1 block w-full" :value="old('website', $artist->website ?? '')" />
        <x-input-error class="mt-2" :messages="$errors->get('website')" />
    </div>

    <!-- Location -->
    <div>
        <x-input-label for="territory_id" :value="__('Comuna normalizada')" />
        <select id="territory_id" name="territory_id" class="mt-1 block w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-indigo-500 dark:focus:border-indigo-600 focus:ring-indigo-500 dark:focus:ring-indigo-600 rounded-md shadow-sm">
            <option value="">{{ __('Seleccionar...') }}</option>
            @foreach($territories as $territory)
                <option value="{{ $territory->id }}" @selected(old('territory_id', $artist->territory_id ?? '') == $territory->id)>
                    {{ $territory->name }} ({{ $territory->region }})
                </option>
            @endforeach
        </select>
        <x-input-error class="mt-2" :messages="$errors->get('territory_id')" />
    </div>

    <div>
        <x-input-label for="region" :value="__('Región (texto libre)')" />
        <x-text-input id="region" name="region" type="text" class="mt-1 block w-full" :value="old('region', $artist->region ?? '')" />
        <x-input-error class="mt-2" :messages="$errors->get('region')" />
    </div>

    <div>
        <x-input-label for="commune" :value="__('Comuna (texto libre)')" />
        <x-text-input id="commune" name="commune" type="text" class="mt-1 block w-full" :value="old('commune', $artist->commune ?? '')" />
        <x-input-error class="mt-2" :messages="$errors->get('commune')" />
    </div>

    <div>
        <x-input-label for="address" :value="__('Dirección')" />
        <x-text-input id="address" name="address" type="text" class="mt-1 block w-full" :value="old('address', $artist->address ?? '')" />
        <x-input-error class="mt-2" :messages="$errors->get('address')" />
    </div>

    <!-- Disciplines -->
    <div>
        <x-input-label for="main_discipline_id" :value="__('Disciplina principal')" />
        <select id="main_discipline_id" name="main_discipline_id" class="mt-1 block w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-indigo-500 dark:focus:border-indigo-600 focus:ring-indigo-500 dark:focus:ring-indigo-600 rounded-md shadow-sm">
            <option value="">{{ __('Seleccionar...') }}</option>
            @foreach($disciplines as $discipline)
                <option value="{{ $discipline->id }}" @selected(old('main_discipline_id', $artist->main_discipline_id ?? '') == $discipline->id)>
                    {{ $discipline->name }}
                </option>
            @endforeach
        </select>
        <x-input-error class="mt-2" :messages="$errors->get('main_discipline_id')" />
    </div>

    <div class="md:col-span-2">
        <x-input-label :value="__('Disciplinas asociadas')" />
        <div class="mt-2 grid grid-cols-2 md:grid-cols-3 gap-2">
            @foreach($disciplines as $discipline)
                @php
                    $selectedDisciplines = old('disciplines', isset($artist) ? $artist->disciplines->pluck('id')->toArray() : []);
                @endphp
                <label class="inline-flex items-center">
                    <input type="checkbox" name="disciplines[]" value="{{ $discipline->id }}" @checked(in_array($discipline->id, $selectedDisciplines)) class="rounded dark:bg-gray-900 border-gray-300 dark:border-gray-700 text-indigo-600 shadow-sm focus:ring-indigo-500 dark:focus:ring-indigo-600 dark:focus:ring-offset-gray-800">
                    <span class="ms-2 text-sm text-gray-600 dark:text-gray-400">{{ $discipline->name }}</span>
                </label>
            @endforeach
        </div>
        <x-input-error class="mt-2" :messages="$errors->get('disciplines')" />
    </div>

    <!-- Biography -->
    <div class="md:col-span-2">
        <x-input-label for="bio_short" :value="__('Biografía corta')" />
        <textarea id="bio_short" name="bio_short" rows="3" class="mt-1 block w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-indigo-500 dark:focus:border-indigo-600 focus:ring-indigo-500 dark:focus:ring-indigo-600 rounded-md shadow-sm">{{ old('bio_short', $artist->bio_short ?? '') }}</textarea>
        <x-input-error class="mt-2" :messages="$errors->get('bio_short')" />
    </div>

    <div class="md:col-span-2">
        <x-input-label for="bio_long" :value="__('Biografía larga')" />
        <textarea id="bio_long" name="bio_long" rows="6" class="mt-1 block w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-indigo-500 dark:focus:border-indigo-600 focus:ring-indigo-500 dark:focus:ring-indigo-600 rounded-md shadow-sm">{{ old('bio_long', $artist->bio_long ?? '') }}</textarea>
        <x-input-error class="mt-2" :messages="$errors->get('bio_long')" />
    </div>

    <!-- Status -->
    @can('artists.approve')
        <div>
            <x-input-label for="status" :value="__('Estado')" />
            <select id="status" name="status" class="mt-1 block w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-indigo-500 dark:focus:border-indigo-600 focus:ring-indigo-500 dark:focus:ring-indigo-600 rounded-md shadow-sm">
                @foreach($statuses as $status)
                    <option value="{{ $status }}" @selected(old('status', $artist->status ?? 'draft') === $status)>
                        {{ __("status.{$status}") }}
                    </option>
                @endforeach
            </select>
            <x-input-error class="mt-2" :messages="$errors->get('status')" />
        </div>
    @endcan
</div>
