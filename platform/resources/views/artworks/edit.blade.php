<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ __('Editar obra') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8 space-y-6">
            @if(session('success'))
                <div class="p-4 bg-green-50 dark:bg-green-900/20 text-green-700 dark:text-green-300 rounded-md">
                    {{ session('success') }}
                </div>
            @endif

            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg p-6">
                <form method="POST" action="{{ route('artworks.update', $artwork) }}" class="space-y-6">
                    @method('PATCH')
                    @include('artworks._form')

                    <div class="flex items-center gap-4">
                        <x-primary-button>{{ __('Guardar cambios') }}</x-primary-button>
                        <a href="{{ route('artworks.show', $artwork) }}" class="text-sm text-gray-600 dark:text-gray-400 hover:underline">{{ __('Ver obra') }}</a>
                    </div>
                </form>
            </div>

            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg p-6">
                <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-4">{{ __('Imágenes') }}</h3>

                <form method="POST" action="{{ route('artworks.media.store', $artwork) }}" enctype="multipart/form-data" class="space-y-4 mb-6">
                    @csrf
                    <div>
                        <x-input-label for="images" :value="__('Subir imágenes')" />
                        <input id="images" name="images[]" type="file" multiple accept="image/*" class="mt-1 block w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-indigo-500 dark:focus:border-indigo-600 focus:ring-indigo-500 dark:focus:ring-indigo-600 rounded-md shadow-sm">
                        <x-input-error :messages="$errors->get('images')" class="mt-2" />
                        <x-input-error :messages="$errors->get('images.*')" class="mt-2" />
                    </div>
                    <x-secondary-button type="submit">{{ __('Subir') }}</x-secondary-button>
                </form>

                @if($artwork->media->isEmpty())
                    <p class="text-gray-600 dark:text-gray-400">{{ __('No hay imágenes aún.') }}</p>
                @else
                    <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 gap-4">
                        @foreach($artwork->media as $media)
                            <div class="relative group border rounded-lg overflow-hidden {{ $media->is_cover ? 'ring-2 ring-indigo-500' : '' }}"
                                 data-media-status-poll
                                 data-media-status="{{ $media->status }}"
                                 data-media-status-url="{{ route('media.status', $media) }}"
                                 data-media-status-thumbnail="1">
                                <img data-media-status-image
                                     @if($media->isCompleted()) src="{{ $media->thumbnailUrl() }}" @endif
                                     alt="" class="w-full h-32 object-cover {{ $media->isCompleted() ? '' : 'hidden' }}">
                                <div data-media-status-spinner class="absolute inset-0 flex items-center justify-center bg-gray-100 dark:bg-gray-900 {{ ($media->isQueued() || $media->isProcessing()) ? '' : 'hidden' }}">
                                    <svg class="animate-spin h-6 w-6 text-gray-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                                    </svg>
                                </div>
                                <div data-media-status-error class="absolute inset-0 items-center justify-center bg-red-50 dark:bg-red-900/40 text-red-600 dark:text-red-300 text-xs p-2 text-center {{ $media->isFailed() ? 'flex' : 'hidden' }}">
                                    {{ $media->isFailed() ? $media->error_message : '' }}
                                </div>
                                @if($media->is_cover)
                                    <span class="absolute top-1 left-1 px-2 py-0.5 text-xs bg-indigo-600 text-white rounded">{{ __('Portada') }}</span>
                                @endif
                                <div class="absolute inset-0 bg-black/50 opacity-0 group-hover:opacity-100 transition flex items-center justify-center gap-2">
                                    @if(! $media->is_cover)
                                        <form method="POST" action="{{ route('artworks.media.cover', [$artwork, $media]) }}" class="inline">
                                            @csrf
                                            @method('PATCH')
                                            <x-secondary-button type="submit" class="text-xs">{{ __('Portada') }}</x-secondary-button>
                                        </form>
                                    @endif
                                    <form method="POST" action="{{ route('artworks.media.destroy', [$artwork, $media]) }}" class="inline" onsubmit="return confirm('¿Eliminar imagen?')">
                                        @csrf
                                        @method('DELETE')
                                        <x-danger-button type="submit" class="text-xs">{{ __('Eliminar') }}</x-danger-button>
                                    </form>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>
    </div>
</x-app-layout>
