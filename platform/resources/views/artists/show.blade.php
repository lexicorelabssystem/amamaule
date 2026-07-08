<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                {{ $artist->displayName() }}
            </h2>
            <div class="flex items-center gap-2">
                @can('update', $artist)
                    <a href="{{ route('artists.edit', $artist) }}" class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800 transition ease-in-out duration-150">
                        {{ __('Editar') }}
                    </a>
                @endcan
                <a href="{{ route('artists.index') }}" class="inline-flex items-center px-4 py-2 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded-md font-semibold text-xs text-gray-700 dark:text-gray-300 uppercase tracking-widest shadow-sm hover:bg-gray-50 dark:hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800 transition ease-in-out duration-150">
                    {{ __('Volver') }}
                </a>
            </div>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            @if(session('status'))
                <div class="p-4 bg-green-50 dark:bg-green-900/20 text-green-700 dark:text-green-300 rounded-md">
                    {{ session('status') }}
                </div>
            @endif
            @if(session('error'))
                <div class="p-4 bg-red-50 dark:bg-red-900/20 text-red-700 dark:text-red-300 rounded-md">
                    {{ session('error') }}
                </div>
            @endif

            <!-- Status card -->
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm text-gray-600 dark:text-gray-400">{{ __('Estado') }}</p>
                        <p class="text-lg font-semibold text-gray-900 dark:text-gray-100">{{ __("status.{$artist->status}") }}</p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-600 dark:text-gray-400">{{ __('Creado el') }}</p>
                        <p class="text-sm text-gray-900 dark:text-gray-100">{{ $artist->created_at->format('d/m/Y H:i') }}</p>
                    </div>
                </div>
            </div>

            @can('wordpress.publish')
                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg p-6">
                    <div class="flex flex-wrap items-start justify-between gap-4">
                        <div>
                            <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100">{{ __('Publicaci?n WordPress') }}</h3>
                            <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                                {{ __('Estado:') }} {{ $artist->wordpressPublication?->status ?? __('sin publicar') }}
                                @if($artist->wordpressPublication?->wordpress_url)
                                    ? <a href="{{ $artist->wordpressPublication->wordpress_url }}" target="_blank" class="text-indigo-600 dark:text-indigo-400 hover:underline">{{ __('Ver en WordPress') }}</a>
                                @endif
                            </p>
                            @if($artist->wordpressPublication?->last_error)
                                <p class="mt-2 text-sm text-red-600 dark:text-red-400">{{ $artist->wordpressPublication->last_error }}</p>
                            @endif
                        </div>
                        <div class="flex flex-wrap gap-2">
                            @if($artist->isApproved())
                                <form method="POST" action="{{ route('artists.wordpress.publish', $artist) }}">
                                    @csrf
                                    <x-primary-button>{{ $artist->wordpressPublication?->wordpress_post_id ? __('Sincronizar') : __('Publicar') }}</x-primary-button>
                                </form>
                            @endif
                            @can('wordpress.unpublish')
                                @if($artist->wordpressPublication?->wordpress_post_id)
                                    <form method="POST" action="{{ route('artists.wordpress.unpublish', $artist) }}">
                                        @csrf
                                        @method('PATCH')
                                        <x-secondary-button>{{ __('Despublicar') }}</x-secondary-button>
                                    </form>
                                @endif
                            @endcan
                        </div>
                    </div>
                </div>
            @endcan

            <!-- Basic info -->
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg p-6">
                <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-4">{{ __('Información básica') }}</h3>
                <dl class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">{{ __('Nombre legal') }}</dt>
                        <dd class="mt-1 text-sm text-gray-900 dark:text-gray-100">{{ $artist->legal_name ?? '-' }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">{{ __('Nombre artístico') }}</dt>
                        <dd class="mt-1 text-sm text-gray-900 dark:text-gray-100">{{ $artist->public_name ?? '-' }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">{{ __('Alias') }}</dt>
                        <dd class="mt-1 text-sm text-gray-900 dark:text-gray-100">{{ $artist->artistic_name ?? '-' }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">{{ __('RUT / Documento') }}</dt>
                        <dd class="mt-1 text-sm text-gray-900 dark:text-gray-100">{{ $artist->document_number ?? '-' }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">{{ __('Email de contacto') }}</dt>
                        <dd class="mt-1 text-sm text-gray-900 dark:text-gray-100">{{ $artist->email_contact ?? '-' }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">{{ __('Teléfono') }}</dt>
                        <dd class="mt-1 text-sm text-gray-900 dark:text-gray-100">{{ $artist->phone ?? '-' }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">{{ __('Sitio web') }}</dt>
                        <dd class="mt-1 text-sm text-gray-900 dark:text-gray-100">
                            @if($artist->website)
                                <a href="{{ $artist->website }}" target="_blank" class="text-indigo-600 dark:text-indigo-400 hover:underline">{{ $artist->website }}</a>
                            @else
                                -
                            @endif
                        </dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">{{ __('Ubicación') }}</dt>
                        <dd class="mt-1 text-sm text-gray-900 dark:text-gray-100">
                            {{ $artist->territory?->name ?? $artist->commune ?? '-' }}
                            @if($artist->region)
                                , {{ $artist->region }}
                            @endif
                        </dd>
                    </div>
                    <div class="md:col-span-2">
                        <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">{{ __('Dirección') }}</dt>
                        <dd class="mt-1 text-sm text-gray-900 dark:text-gray-100">{{ $artist->address ?? '-' }}</dd>
                    </div>
                </dl>
            </div>

            <!-- Disciplines -->
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg p-6">
                <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-4">{{ __('Disciplinas') }}</h3>
                <p class="text-sm text-gray-600 dark:text-gray-400">
                    {{ __('Principal:') }} <span class="font-medium text-gray-900 dark:text-gray-100">{{ $artist->mainDiscipline?->name ?? '-' }}</span>
                </p>
                @if($artist->disciplines->isNotEmpty())
                    <div class="mt-2 flex flex-wrap gap-2">
                        @foreach($artist->disciplines as $discipline)
                            <span class="px-2 py-1 text-xs font-semibold rounded-full bg-indigo-100 text-indigo-800 dark:bg-indigo-900 dark:text-indigo-200">
                                {{ $discipline->name }}
                            </span>
                        @endforeach
                    </div>
                @endif
            </div>

            <!-- Biography -->
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg p-6">
                <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-4">{{ __('Biografía') }}</h3>
                <div class="prose dark:prose-invert max-w-none">
                    @if($artist->bio_long)
                        <p class="text-sm text-gray-700 dark:text-gray-300 whitespace-pre-line">{{ $artist->bio_long }}</p>
                    @elseif($artist->bio_short)
                        <p class="text-sm text-gray-700 dark:text-gray-300">{{ $artist->bio_short }}</p>
                    @else
                        <p class="text-sm text-gray-500 dark:text-gray-400">{{ __('Sin biografía registrada.') }}</p>
                    @endif
                </div>
            </div>

            @can('delete', $artist)
                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg p-6">
                    <h3 class="text-lg font-medium text-red-600 dark:text-red-400 mb-4">{{ __('Zona de peligro') }}</h3>
                    <form method="POST" action="{{ route('artists.destroy', $artist) }}" onsubmit="return confirm('{{ __('¿Estás seguro de eliminar este artista?') }}')">
                        @csrf
                        @method('DELETE')
                        <x-danger-button>
                            {{ __('Eliminar artista') }}
                        </x-danger-button>
                    </form>
                </div>
            @endcan
        </div>
    </div>
</x-app-layout>
