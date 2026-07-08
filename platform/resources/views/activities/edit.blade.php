<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                {{ __('Editar actividad') }}
            </h2>
            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-200">
                {{ $activity->status }}
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

            <!-- Form -->
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg p-6">
                <form method="POST" action="{{ route('activities.update', $activity) }}" class="space-y-6">
                    @csrf
                    @method('PATCH')
                    @include('activities._form')

                    <div class="flex items-center gap-4">
                        <x-primary-button>{{ __('Guardar cambios') }}</x-primary-button>
                        <a href="{{ route('activities.show', $activity) }}" class="text-sm text-gray-600 dark:text-gray-400 hover:underline">{{ __('Ver actividad') }}</a>
                    </div>
                </form>
            </div>

            <!-- Gallery -->
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg p-6">
                <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-4">{{ __('Galería') }}</h3>

                <form method="POST" action="{{ route('activities.media.store', $activity) }}" enctype="multipart/form-data" class="space-y-4 mb-6">
                    @csrf
                    <div>
                        <x-input-label for="images" :value="__('Subir imágenes')" />
                        <input id="images" name="images[]" type="file" multiple accept="image/*" class="mt-1 block w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-indigo-500 dark:focus:border-indigo-600 focus:ring-indigo-500 dark:focus:ring-indigo-600 rounded-md shadow-sm">
                        <x-input-error :messages="$errors->get('images')" class="mt-2" />
                        <x-input-error :messages="$errors->get('images.*')" class="mt-2" />
                    </div>
                    <x-secondary-button type="submit">{{ __('Subir') }}</x-secondary-button>
                </form>

                @if($activity->media->isEmpty())
                    <p class="text-gray-600 dark:text-gray-400">{{ __('No hay imágenes aún.') }}</p>
                @else
                    <form method="POST" action="{{ route('activities.media.reorder', $activity) }}" class="space-y-4">
                        @csrf
                        <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 gap-4">
                            @foreach($activity->media as $media)
                                <div class="relative group border rounded-lg overflow-hidden {{ $media->is_cover ? 'ring-2 ring-indigo-500' : '' }}">
                                    <img src="{{ $media->thumbnailUrl() }}" alt="" class="w-full h-32 object-cover">
                                    @if($media->is_cover)
                                        <span class="absolute top-1 left-1 px-2 py-0.5 text-xs bg-indigo-600 text-white rounded">{{ __('Portada') }}</span>
                                    @endif
                                    <input type="hidden" name="order[]" value="{{ $media->id }}">
                                    <div class="absolute inset-0 bg-black/50 opacity-0 group-hover:opacity-100 transition flex items-center justify-center gap-2">
                                        @if(! $media->is_cover)
                                            <form method="POST" action="{{ route('activities.media.cover', [$activity, $media]) }}" class="inline">
                                                @csrf
                                                @method('PATCH')
                                                <x-secondary-button type="submit" class="text-xs">{{ __('Portada') }}</x-secondary-button>
                                            </form>
                                        @endif
                                        <form method="POST" action="{{ route('activities.media.destroy', [$activity, $media]) }}" class="inline" onsubmit="return confirm('¿Eliminar imagen?')">
                                            @csrf
                                            @method('DELETE')
                                            <x-danger-button type="submit" class="text-xs">{{ __('Eliminar') }}</x-danger-button>
                                        </form>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                        <x-secondary-button type="submit">{{ __('Guardar orden') }}</x-secondary-button>
                    </form>
                @endif
            </div>
        </div>
    </div>
</x-app-layout>
