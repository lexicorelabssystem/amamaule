<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                {{ __('Mi perfil artístico') }}
            </h2>
            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-200">
                {{ $artist->status }}
            </span>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8 space-y-6">
            @if(session('success'))
                <div class="p-4 bg-green-50 dark:bg-green-900/20 text-green-700 dark:text-green-300 rounded-md">
                    {{ session('success') }}
                </div>
            @endif
            @if(session('error'))
                <div class="p-4 bg-red-50 dark:bg-red-900/20 text-red-700 dark:text-red-300 rounded-md">
                    {{ session('error') }}
                </div>
            @endif

            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg p-6">
                <form method="POST" action="{{ route('profile.update') }}" class="space-y-6">
                    @csrf
                    @method('PATCH')

                    <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100">{{ __('Información general') }}</h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <x-input-label for="legal_name" :value="__('Nombre legal')" />
                            <x-text-input id="legal_name" name="legal_name" type="text" class="mt-1 block w-full" :value="old('legal_name', $artist->legal_name)" required />
                            <x-input-error :messages="$errors->get('legal_name')" class="mt-2" />
                        </div>

                        <div>
                            <x-input-label for="public_name" :value="__('Nombre público')" />
                            <x-text-input id="public_name" name="public_name" type="text" class="mt-1 block w-full" :value="old('public_name', $artist->public_name)" required />
                            <x-input-error :messages="$errors->get('public_name')" class="mt-2" />
                        </div>

                        <div>
                            <x-input-label for="artistic_name" :value="__('Nombre artístico')" />
                            <x-text-input id="artistic_name" name="artistic_name" type="text" class="mt-1 block w-full" :value="old('artistic_name', $artist->artistic_name)" />
                            <x-input-error :messages="$errors->get('artistic_name')" class="mt-2" />
                        </div>

                        <div>
                            <x-input-label for="email_contact" :value="__('Email de contacto')" />
                            <x-text-input id="email_contact" name="email_contact" type="email" class="mt-1 block w-full" :value="old('email_contact', $artist->email_contact)" required />
                            <x-input-error :messages="$errors->get('email_contact')" class="mt-2" />
                        </div>

                        <div>
                            <x-input-label for="phone" :value="__('Teléfono')" />
                            <x-text-input id="phone" name="phone" type="text" class="mt-1 block w-full" :value="old('phone', $artist->phone)" />
                            <x-input-error :messages="$errors->get('phone')" class="mt-2" />
                        </div>

                        <div>
                            <x-input-label for="website" :value="__('Sitio web')" />
                            <x-text-input id="website" name="website" type="url" class="mt-1 block w-full" :value="old('website', $artist->website)" />
                            <x-input-error :messages="$errors->get('website')" class="mt-2" />
                        </div>

                        <div>
                            <x-input-label for="document_number" :value="__('RUT / Documento')" />
                            <x-text-input id="document_number" name="document_number" type="text" class="mt-1 block w-full" :value="old('document_number', $artist->document_number)" />
                            <x-input-error :messages="$errors->get('document_number')" class="mt-2" />
                        </div>

                        <div>
                            <x-input-label for="main_discipline_id" :value="__('Disciplina principal')" />
                            <select id="main_discipline_id" name="main_discipline_id" class="mt-1 block w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-indigo-500 dark:focus:border-indigo-600 focus:ring-indigo-500 dark:focus:ring-indigo-600 rounded-md shadow-sm">
                                <option value="">{{ __('Seleccionar') }}</option>
                                @foreach($disciplines as $id => $name)
                                    <option value="{{ $id }}" @selected(old('main_discipline_id', $artist->main_discipline_id) == $id)>{{ $name }}</option>
                                @endforeach
                            </select>
                            <x-input-error :messages="$errors->get('main_discipline_id')" class="mt-2" />
                        </div>

                        <div>
                            <x-input-label for="territory_id" :value="__('Comuna / Territorio')" />
                            <select id="territory_id" name="territory_id" class="mt-1 block w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-indigo-500 dark:focus:border-indigo-600 focus:ring-indigo-500 dark:focus:ring-indigo-600 rounded-md shadow-sm">
                                <option value="">{{ __('Seleccionar') }}</option>
                                @foreach($territories as $id => $name)
                                    <option value="{{ $id }}" @selected(old('territory_id', $artist->territory_id) == $id)>{{ $name }}</option>
                                @endforeach
                            </select>
                            <x-input-error :messages="$errors->get('territory_id')" class="mt-2" />
                        </div>

                        <div>
                            <x-input-label for="region" :value="__('Región')" />
                            <x-text-input id="region" name="region" type="text" class="mt-1 block w-full" :value="old('region', $artist->region)" />
                            <x-input-error :messages="$errors->get('region')" class="mt-2" />
                        </div>

                        <div>
                            <x-input-label for="commune" :value="__('Comuna')" />
                            <x-text-input id="commune" name="commune" type="text" class="mt-1 block w-full" :value="old('commune', $artist->commune)" />
                            <x-input-error :messages="$errors->get('commune')" class="mt-2" />
                        </div>

                        <div class="md:col-span-2">
                            <x-input-label for="address" :value="__('Dirección')" />
                            <x-text-input id="address" name="address" type="text" class="mt-1 block w-full" :value="old('address', $artist->address)" />
                            <x-input-error :messages="$errors->get('address')" class="mt-2" />
                        </div>
                    </div>

                    <div class="grid grid-cols-1 gap-6">
                        <div>
                            <x-input-label for="bio_short" :value="__('Biografía corta')" />
                            <textarea id="bio_short" name="bio_short" rows="3" class="mt-1 block w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-indigo-500 dark:focus:border-indigo-600 focus:ring-indigo-500 dark:focus:ring-indigo-600 rounded-md shadow-sm">{{ old('bio_short', $artist->bio_short) }}</textarea>
                            <x-input-error :messages="$errors->get('bio_short')" class="mt-2" />
                        </div>

                        <div>
                            <x-input-label for="bio_long" :value="__('Biografía extendida')" />
                            <textarea id="bio_long" name="bio_long" rows="6" class="mt-1 block w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-indigo-500 dark:focus:border-indigo-600 focus:ring-indigo-500 dark:focus:ring-indigo-600 rounded-md shadow-sm">{{ old('bio_long', $artist->bio_long) }}</textarea>
                            <x-input-error :messages="$errors->get('bio_long')" class="mt-2" />
                        </div>
                    </div>

                    <hr class="border-gray-200 dark:border-gray-700">

                    <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100">{{ __('Perfil detallado') }}</h3>
                    @php($profile = $artist->profile)
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div class="md:col-span-2">
                            <x-input-label for="experience" :value="__('Experiencia')" />
                            <textarea id="experience" name="experience" rows="4" class="mt-1 block w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-indigo-500 dark:focus:border-indigo-600 focus:ring-indigo-500 dark:focus:ring-indigo-600 rounded-md shadow-sm">{{ old('experience', $profile?->experience) }}</textarea>
                            <x-input-error :messages="$errors->get('experience')" class="mt-2" />
                        </div>

                        <div class="md:col-span-2">
                            <x-input-label for="education" :value="__('Formación')" />
                            <textarea id="education" name="education" rows="4" class="mt-1 block w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-indigo-500 dark:focus:border-indigo-600 focus:ring-indigo-500 dark:focus:ring-indigo-600 rounded-md shadow-sm">{{ old('education', $profile?->education) }}</textarea>
                            <x-input-error :messages="$errors->get('education')" class="mt-2" />
                        </div>

                        <div class="md:col-span-2">
                            <x-input-label for="awards" :value="__('Premios / reconocimientos')" />
                            <textarea id="awards" name="awards" rows="4" class="mt-1 block w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-indigo-500 dark:focus:border-indigo-600 focus:ring-indigo-500 dark:focus:ring-indigo-600 rounded-md shadow-sm">{{ old('awards', $profile?->awards) }}</textarea>
                            <x-input-error :messages="$errors->get('awards')" class="mt-2" />
                        </div>

                        <div>
                            <x-input-label for="portfolio_url" :value="__('URL portafolio')" />
                            <x-text-input id="portfolio_url" name="portfolio_url" type="url" class="mt-1 block w-full" :value="old('portfolio_url', $profile?->portfolio_url)" />
                            <x-input-error :messages="$errors->get('portfolio_url')" class="mt-2" />
                        </div>

                        <div>
                            <x-input-label for="video_url" :value="__('URL video')" />
                            <x-text-input id="video_url" name="video_url" type="url" class="mt-1 block w-full" :value="old('video_url', $profile?->video_url)" />
                            <x-input-error :messages="$errors->get('video_url')" class="mt-2" />
                        </div>

                        <div>
                            <x-input-label for="availability" :value="__('Disponibilidad')" />
                            <x-text-input id="availability" name="availability" type="text" class="mt-1 block w-full" :value="old('availability', $profile?->availability)" />
                            <x-input-error :messages="$errors->get('availability')" class="mt-2" />
                        </div>

                        <div>
                            <x-input-label for="representation" :value="__('Representante')" />
                            <x-text-input id="representation" name="representation" type="text" class="mt-1 block w-full" :value="old('representation', $profile?->representation)" />
                            <x-input-error :messages="$errors->get('representation')" class="mt-2" />
                        </div>

                        <div class="md:col-span-2">
                            <x-input-label for="press_links" :value="__('Enlaces de prensa')" />
                            @php($links = old('press_links', $profile?->press_links ?? []))
                            <div class="space-y-2 mt-1">
                                @for($i = 0; $i < 3; $i++)
                                    <x-text-input name="press_links[]" type="url" class="block w-full" :value="$links[$i] ?? null" placeholder="https://..." />
                                @endfor
                            </div>
                            <x-input-error :messages="$errors->get('press_links')" class="mt-2" />
                        </div>

                        <div class="md:col-span-2">
                            <x-input-label for="tech_rider" :value="__('Rider técnico')" />
                            <textarea id="tech_rider" name="tech_rider" rows="4" class="mt-1 block w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-indigo-500 dark:focus:border-indigo-600 focus:ring-indigo-500 dark:focus:ring-indigo-600 rounded-md shadow-sm">{{ old('tech_rider', $profile?->tech_rider) }}</textarea>
                            <x-input-error :messages="$errors->get('tech_rider')" class="mt-2" />
                        </div>

                        <div class="md:col-span-2">
                            <x-input-label for="stage_requirements" :value="__('Requerimientos de escenario')" />
                            <textarea id="stage_requirements" name="stage_requirements" rows="4" class="mt-1 block w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-indigo-500 dark:focus:border-indigo-600 focus:ring-indigo-500 dark:focus:ring-indigo-600 rounded-md shadow-sm">{{ old('stage_requirements', $profile?->stage_requirements) }}</textarea>
                            <x-input-error :messages="$errors->get('stage_requirements')" class="mt-2" />
                        </div>
                    </div>

                    <div class="flex items-center gap-4">
                        <x-primary-button>{{ __('Guardar borrador') }}</x-primary-button>
                    </div>
                </form>

                @if($artist->isDraft())
                    <form method="POST" action="{{ route('profile.submit') }}" class="mt-6 pt-6 border-t border-gray-200 dark:border-gray-700">
                        @csrf
                        <x-secondary-button type="submit">{{ __('Enviar a revisión') }}</x-secondary-button>
                    </form>
                @endif
            </div>
        </div>
    </div>
</x-app-layout>
