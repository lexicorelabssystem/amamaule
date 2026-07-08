<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                {{ $activity->title }}
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

            <!-- Actions -->
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg p-6">
                <div class="flex flex-wrap gap-3">
                    @can('update', $activity)
                        <a href="{{ route('activities.edit', $activity) }}" class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700">
                            {{ __('Editar') }}
                        </a>
                    @endcan
                    @can('publish', $activity)
                        <form method="POST" action="{{ route('activities.publish', $activity) }}">
                            @csrf
                            @method('PATCH')
                            <x-primary-button type="submit">{{ __('Publicar') }}</x-primary-button>
                        </form>
                    @endcan
                    @can('archive', $activity)
                        <form method="POST" action="{{ route('activities.archive', $activity) }}">
                            @csrf
                            @method('PATCH')
                            <x-secondary-button type="submit">{{ __('Archivar') }}</x-secondary-button>
                        </form>
                    @endcan
                </div>
            </div>

            <!-- Details -->
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg p-6 space-y-4">
                <dl class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-sm">
                    <div>
                        <dt class="text-gray-500 dark:text-gray-400">{{ __('Artista') }}</dt>
                        <dd class="font-medium text-gray-900 dark:text-gray-100">{{ $activity->artist->public_name }}</dd>
                    </div>
                    <div>
                        <dt class="text-gray-500 dark:text-gray-400">{{ __('Fecha inicio') }}</dt>
                        <dd class="font-medium text-gray-900 dark:text-gray-100">{{ $activity->start_date?->format('d/m/Y H:i') ?? '-' }}</dd>
                    </div>
                    <div>
                        <dt class="text-gray-500 dark:text-gray-400">{{ __('Fecha término') }}</dt>
                        <dd class="font-medium text-gray-900 dark:text-gray-100">{{ $activity->end_date?->format('d/m/Y H:i') ?? '-' }}</dd>
                    </div>
                    <div>
                        <dt class="text-gray-500 dark:text-gray-400">{{ __('Lugar') }}</dt>
                        <dd class="font-medium text-gray-900 dark:text-gray-100">{{ $activity->location ?? '-' }}</dd>
                    </div>
                    <div>
                        <dt class="text-gray-500 dark:text-gray-400">{{ __('Comuna') }}</dt>
                        <dd class="font-medium text-gray-900 dark:text-gray-100">{{ $activity->territory?->name ?? '-' }}</dd>
                    </div>
                    <div>
                        <dt class="text-gray-500 dark:text-gray-400">{{ __('Precio') }}</dt>
                        <dd class="font-medium text-gray-900 dark:text-gray-100">{{ $activity->is_free ? 'Gratuita' : ($activity->price ?? '-') }}</dd>
                    </div>
                </dl>

                @if($activity->description)
                    <div>
                        <h3 class="text-sm font-medium text-gray-900 dark:text-gray-100">{{ __('Descripción') }}</h3>
                        <p class="mt-1 text-gray-600 dark:text-gray-400 whitespace-pre-line">{{ $activity->description }}</p>
                    </div>
                @endif
            </div>

            <!-- Gallery -->
            @if($activity->media->isNotEmpty())
                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg p-6">
                    <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-4">{{ __('Galería') }}</h3>
                    <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 gap-4">
                        @foreach($activity->media as $media)
                            <div class="relative border rounded-lg overflow-hidden {{ $media->is_cover ? 'ring-2 ring-indigo-500' : '' }}">
                                <img src="{{ $media->thumbnailUrl() }}" alt="" class="w-full h-40 object-cover">
                                @if($media->is_cover)
                                    <span class="absolute top-1 left-1 px-2 py-0.5 text-xs bg-indigo-600 text-white rounded">{{ __('Portada') }}</span>
                                @endif
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif
        </div>
    </div>
</x-app-layout>
