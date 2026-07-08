<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                {{ __('Revisar perfil') }}
            </h2>
            <a href="{{ route('profile-reviews.index') }}" class="text-sm text-indigo-600 dark:text-indigo-400 hover:underline">
                {{ __('Volver a la bandeja') }}
            </a>
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
                    @can('approve', $artist)
                        <form method="POST" action="{{ route('profile-reviews.approve', $artist) }}">
                            @csrf
                            @method('PATCH')
                            <x-primary-button type="submit">{{ __('Aprobar') }}</x-primary-button>
                        </form>
                    @endcan

                    @can('requestChanges', $artist)
                        <form method="POST" action="{{ route('profile-reviews.request-changes', $artist) }}">
                            @csrf
                            @method('PATCH')
                            <x-secondary-button type="submit">{{ __('Solicitar cambios') }}</x-secondary-button>
                        </form>
                    @endcan

                    @can('reject', $artist)
                        <form method="POST" action="{{ route('profile-reviews.reject', $artist) }}">
                            @csrf
                            @method('PATCH')
                            <x-danger-button type="submit">{{ __('Rechazar') }}</x-danger-button>
                        </form>
                    @endcan
                </div>
            </div>

            <!-- Profile summary -->
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg p-6 space-y-4">
                <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100">{{ __('Datos generales') }}</h3>
                <dl class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-sm">
                    <div>
                        <dt class="text-gray-500 dark:text-gray-400">{{ __('Nombre legal') }}</dt>
                        <dd class="font-medium text-gray-900 dark:text-gray-100">{{ $artist->legal_name }}</dd>
                    </div>
                    <div>
                        <dt class="text-gray-500 dark:text-gray-400">{{ __('Nombre público') }}</dt>
                        <dd class="font-medium text-gray-900 dark:text-gray-100">{{ $artist->public_name }}</dd>
                    </div>
                    <div>
                        <dt class="text-gray-500 dark:text-gray-400">{{ __('Email de contacto') }}</dt>
                        <dd class="font-medium text-gray-900 dark:text-gray-100">{{ $artist->email_contact }}</dd>
                    </div>
                    <div>
                        <dt class="text-gray-500 dark:text-gray-400">{{ __('Disciplina principal') }}</dt>
                        <dd class="font-medium text-gray-900 dark:text-gray-100">{{ $artist->mainDiscipline?->name ?? '-' }}</dd>
                    </div>
                    <div>
                        <dt class="text-gray-500 dark:text-gray-400">{{ __('Comuna / Territorio') }}</dt>
                        <dd class="font-medium text-gray-900 dark:text-gray-100">{{ $artist->territory?->name ?? $artist->commune ?? '-' }}</dd>
                    </div>
                    <div>
                        <dt class="text-gray-500 dark:text-gray-400">{{ __('Usuario') }}</dt>
                        <dd class="font-medium text-gray-900 dark:text-gray-100">{{ $artist->user->name }} ({{ $artist->user->email }})</dd>
                    </div>
                </dl>

                @if($artist->bio_short)
                    <div>
                        <h4 class="text-sm font-medium text-gray-900 dark:text-gray-100">{{ __('Biografía corta') }}</h4>
                        <p class="mt-1 text-sm text-gray-600 dark:text-gray-400 whitespace-pre-line">{{ $artist->bio_short }}</p>
                    </div>
                @endif

                @if($artist->bio_long)
                    <div>
                        <h4 class="text-sm font-medium text-gray-900 dark:text-gray-100">{{ __('Biografía extendida') }}</h4>
                        <p class="mt-1 text-sm text-gray-600 dark:text-gray-400 whitespace-pre-line">{{ $artist->bio_long }}</p>
                    </div>
                @endif
            </div>

            @if($artist->profile)
                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg p-6 space-y-4">
                    <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100">{{ __('Perfil detallado') }}</h3>

                    @if($artist->profile->experience)
                        <div>
                            <h4 class="text-sm font-medium text-gray-900 dark:text-gray-100">{{ __('Experiencia') }}</h4>
                            <p class="mt-1 text-sm text-gray-600 dark:text-gray-400 whitespace-pre-line">{{ $artist->profile->experience }}</p>
                        </div>
                    @endif

                    @if($artist->profile->education)
                        <div>
                            <h4 class="text-sm font-medium text-gray-900 dark:text-gray-100">{{ __('Formación') }}</h4>
                            <p class="mt-1 text-sm text-gray-600 dark:text-gray-400 whitespace-pre-line">{{ $artist->profile->education }}</p>
                        </div>
                    @endif

                    @if($artist->profile->awards)
                        <div>
                            <h4 class="text-sm font-medium text-gray-900 dark:text-gray-100">{{ __('Premios') }}</h4>
                            <p class="mt-1 text-sm text-gray-600 dark:text-gray-400 whitespace-pre-line">{{ $artist->profile->awards }}</p>
                        </div>
                    @endif
                </div>
            @endif

            <!-- Internal comments -->
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg p-6 space-y-6">
                <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100">{{ __('Comentarios internos') }}</h3>

                @can('comments.create_internal')
                    <form method="POST" action="{{ route('comments.store') }}" class="space-y-4">
                        @csrf
                        <input type="hidden" name="commentable_type" value="{{ get_class($artist) }}">
                        <input type="hidden" name="commentable_id" value="{{ $artist->id }}">

                        <div>
                            <x-input-label for="body" :value="__('Nuevo comentario')" />
                            <textarea id="body" name="body" rows="3" class="mt-1 block w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-indigo-500 dark:focus:border-indigo-600 focus:ring-indigo-500 dark:focus:ring-indigo-600 rounded-md shadow-sm" required>{{ old('body') }}</textarea>
                            <x-input-error :messages="$errors->get('body')" class="mt-2" />
                        </div>

                        <x-primary-button type="submit">{{ __('Guardar comentario') }}</x-primary-button>
                    </form>
                @endcan

                @if($artist->comments->isEmpty())
                    <p class="text-gray-600 dark:text-gray-400">{{ __('No hay comentarios aún.') }}</p>
                @else
                    <div class="space-y-4">
                        @foreach($artist->comments as $comment)
                            <div class="p-4 bg-gray-50 dark:bg-gray-700/50 rounded-md">
                                <div class="flex items-center justify-between">
                                    <span class="text-sm font-medium text-gray-900 dark:text-gray-100">{{ $comment->user->name }}</span>
                                    <span class="text-xs text-gray-500 dark:text-gray-400">{{ $comment->created_at->format('d/m/Y H:i') }}</span>
                                </div>
                                <p class="mt-2 text-sm text-gray-700 dark:text-gray-300 whitespace-pre-line">{{ $comment->body }}</p>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>
    </div>
</x-app-layout>
