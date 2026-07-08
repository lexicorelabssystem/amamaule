@csrf

<div class="grid grid-cols-1 md:grid-cols-2 gap-6">
    <div class="md:col-span-2">
        <x-input-label for="title" :value="__('Título')" />
        <x-text-input id="title" name="title" type="text" class="mt-1 block w-full" :value="old('title', $activity->title ?? '')" required />
        <x-input-error :messages="$errors->get('title')" class="mt-2" />
    </div>

    @if(count($artists) > 1)
        <div class="md:col-span-2">
            <x-input-label for="artist_id" :value="__('Artista')" />
            <select id="artist_id" name="artist_id" class="mt-1 block w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-indigo-500 dark:focus:border-indigo-600 focus:ring-indigo-500 dark:focus:ring-indigo-600 rounded-md shadow-sm">
                @foreach($artists as $id => $name)
                    <option value="{{ $id }}" @selected(old('artist_id', $activity->artist_id ?? $userArtist?->id) == $id)>{{ $name }}</option>
                @endforeach
            </select>
            <x-input-error :messages="$errors->get('artist_id')" class="mt-2" />
        </div>
    @elseif(count($artists) === 1)
        <input type="hidden" name="artist_id" value="{{ array_key_first($artists) }}">
    @endif

    <div class="md:col-span-2">
        <x-input-label for="short_description" :value="__('Descripción corta')" />
        <textarea id="short_description" name="short_description" rows="2" class="mt-1 block w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-indigo-500 dark:focus:border-indigo-600 focus:ring-indigo-500 dark:focus:ring-indigo-600 rounded-md shadow-sm">{{ old('short_description', $activity->short_description ?? '') }}</textarea>
        <x-input-error :messages="$errors->get('short_description')" class="mt-2" />
    </div>

    <div class="md:col-span-2">
        <x-input-label for="description" :value="__('Descripción')" />
        <textarea id="description" name="description" rows="5" class="mt-1 block w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-indigo-500 dark:focus:border-indigo-600 focus:ring-indigo-500 dark:focus:ring-indigo-600 rounded-md shadow-sm">{{ old('description', $activity->description ?? '') }}</textarea>
        <x-input-error :messages="$errors->get('description')" class="mt-2" />
    </div>

    <div>
        <x-input-label for="start_date" :value="__('Fecha inicio')" />
        <x-text-input id="start_date" name="start_date" type="datetime-local" class="mt-1 block w-full" :value="old('start_date', isset($activity) && $activity->start_date ? $activity->start_date->format('Y-m-d\TH:i') : '')" />
        <x-input-error :messages="$errors->get('start_date')" class="mt-2" />
    </div>

    <div>
        <x-input-label for="end_date" :value="__('Fecha término')" />
        <x-text-input id="end_date" name="end_date" type="datetime-local" class="mt-1 block w-full" :value="old('end_date', isset($activity) && $activity->end_date ? $activity->end_date->format('Y-m-d\TH:i') : '')" />
        <x-input-error :messages="$errors->get('end_date')" class="mt-2" />
    </div>

    <div>
        <x-input-label for="location" :value="__('Lugar')" />
        <x-text-input id="location" name="location" type="text" class="mt-1 block w-full" :value="old('location', $activity->location ?? '')" />
        <x-input-error :messages="$errors->get('location')" class="mt-2" />
    </div>

    <div>
        <x-input-label for="territory_id" :value="__('Comuna / Territorio')" />
        <select id="territory_id" name="territory_id" class="mt-1 block w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-indigo-500 dark:focus:border-indigo-600 focus:ring-indigo-500 dark:focus:ring-indigo-600 rounded-md shadow-sm">
            <option value="">{{ __('Seleccionar') }}</option>
            @foreach($territories as $id => $name)
                <option value="{{ $id }}" @selected(old('territory_id', $activity->territory_id ?? '') == $id)>{{ $name }}</option>
            @endforeach
        </select>
        <x-input-error :messages="$errors->get('territory_id')" class="mt-2" />
    </div>

    <div>
        <x-input-label for="category" :value="__('Categoría')" />
        <x-text-input id="category" name="category" type="text" class="mt-1 block w-full" :value="old('category', $activity->category ?? '')" />
        <x-input-error :messages="$errors->get('category')" class="mt-2" />
    </div>

    <div>
        <x-input-label for="capacity" :value="__('Capacidad')" />
        <x-text-input id="capacity" name="capacity" type="number" class="mt-1 block w-full" :value="old('capacity', $activity->capacity ?? '')" />
        <x-input-error :messages="$errors->get('capacity')" class="mt-2" />
    </div>

    <div class="flex items-center gap-4">
        <input id="is_free" name="is_free" type="checkbox" value="1" class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500" @checked(old('is_free', $activity->is_free ?? true))>
        <x-input-label for="is_free" :value="__('Gratuita')" />
        <x-input-error :messages="$errors->get('is_free')" class="mt-2" />
    </div>

    <div>
        <x-input-label for="price" :value="__('Precio')" />
        <x-text-input id="price" name="price" type="number" step="0.01" class="mt-1 block w-full" :value="old('price', $activity->price ?? '')" />
        <x-input-error :messages="$errors->get('price')" class="mt-2" />
    </div>
</div>
