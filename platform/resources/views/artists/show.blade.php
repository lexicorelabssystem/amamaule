@php
    $accents = ['coral', 'magenta', 'amber', 'teal', 'indigo'];
    $accentFor = fn ($seed) => $accents[$seed % count($accents)];
    $initials = function (?string $name) {
        $words = array_filter(explode(' ', trim($name ?? '')));
        $letters = array_map(fn ($w) => mb_strtoupper(mb_substr($w, 0, 1)), array_slice($words, 0, 2));

        return implode('', $letters) ?: '?';
    };
    $isOwn = $artist->user_id === auth()->id();
@endphp

<x-app-layout>
    <div class="py-10">
        <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">
            @if(session('status'))
                <div class="p-4 bg-white border border-ama-teal/30 text-ama-ink rounded-lg">{{ session('status') }}</div>
            @endif
            @if(session('error'))
                <div class="p-4 bg-white border border-ama-coral/40 text-ama-ink rounded-lg">{{ session('error') }}</div>
            @endif
            @if($errors->has('avatar') || $errors->has('cover'))
                <div class="p-4 bg-white border border-ama-coral/40 text-ama-ink rounded-lg">
                    {{ $errors->first('avatar') ?: $errors->first('cover') }}
                </div>
            @endif

            {{-- Profile header --}}
            <div class="bg-white rounded-2xl overflow-hidden border border-ama-ink/5">
                <div class="relative h-32 {{ $artist->cover ? '' : 'bg-ama-gradient' }}"
                     @if($artist->cover)
                         data-media-status-poll
                         data-media-status="{{ $artist->cover->status }}"
                         data-media-status-url="{{ route('media.status', $artist->cover) }}"
                     @endif
                >
                    @if($artist->cover)
                        <img data-media-status-image
                             @if($artist->cover->isCompleted()) src="{{ $artist->cover->fullUrl() }}" @endif
                             alt="" class="w-full h-full object-cover {{ $artist->cover->isCompleted() ? '' : 'hidden' }}">
                        <div data-media-status-spinner class="absolute inset-0 flex items-center justify-center bg-white/60 {{ ($artist->cover->isQueued() || $artist->cover->isProcessing()) ? '' : 'hidden' }}">
                            <svg class="animate-spin h-6 w-6 text-ama-ink" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                            </svg>
                        </div>
                        <div data-media-status-error class="absolute inset-0 items-center justify-center bg-red-50 text-ama-coral text-xs p-2 text-center {{ $artist->cover->isFailed() ? 'flex' : 'hidden' }}">
                            {{ $artist->cover->isFailed() ? $artist->cover->error_message : '' }}
                        </div>
                    @endif
                    @if($isOwn)
                        <form method="POST" action="{{ route('artists.cover', $artist) }}" enctype="multipart/form-data">
                            @csrf
                            <label class="absolute bottom-2 right-2 px-3 py-1.5 rounded-full bg-black/50 text-white text-xs font-semibold cursor-pointer hover:bg-black/60">
                                {{ __('Cambiar portada') }}
                                <input type="file" name="cover" accept="image/*" class="hidden" onchange="this.form.submit()">
                            </label>
                        </form>
                    @endif
                </div>
                <div class="px-6 pb-6">
                    <div class="flex flex-wrap items-end justify-between gap-4 -mt-10">
                        <div class="flex items-end gap-4">
                            <div class="relative h-24 w-24">
                                <span class="relative flex h-24 w-24 items-center justify-center rounded-full bg-white border-4 border-white shadow-sm text-3xl font-bold text-ama-{{ $accentFor($artist->id) }} bg-ama-{{ $accentFor($artist->id) }}/15 overflow-hidden"
                                      @if($artist->avatar)
                                          data-media-status-poll
                                          data-media-status="{{ $artist->avatar->status }}"
                                          data-media-status-url="{{ route('media.status', $artist->avatar) }}"
                                      @endif
                                >
                                    @if($artist->avatar)
                                        <img data-media-status-image
                                             @if($artist->avatar->isCompleted()) src="{{ $artist->avatar->fullUrl() }}" @endif
                                             alt="" class="w-full h-full object-cover {{ $artist->avatar->isCompleted() ? '' : 'hidden' }}">
                                        <div data-media-status-spinner class="absolute inset-0 flex items-center justify-center bg-white/60 {{ ($artist->avatar->isQueued() || $artist->avatar->isProcessing()) ? '' : 'hidden' }}">
                                            <svg class="animate-spin h-5 w-5 text-ama-ink" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                                            </svg>
                                        </div>
                                        <div data-media-status-error class="absolute inset-0 items-center justify-center bg-red-50 text-ama-coral text-[10px] p-1 text-center {{ $artist->avatar->isFailed() ? 'flex' : 'hidden' }}">
                                            {{ $artist->avatar->isFailed() ? __('Error') : '' }}
                                        </div>
                                    @else
                                        {{ $initials($artist->displayName()) }}
                                    @endif
                                </span>
                                @if($isOwn)
                                    <form method="POST" action="{{ route('artists.avatar', $artist) }}" enctype="multipart/form-data">
                                        @csrf
                                        <label class="absolute bottom-0 right-0 h-7 w-7 flex items-center justify-center rounded-full bg-ama-ink text-white text-xs cursor-pointer hover:opacity-90" title="{{ __('Cambiar foto') }}">
                                            ✎
                                            <input type="file" name="avatar" accept="image/*" class="hidden" onchange="this.form.submit()">
                                        </label>
                                    </form>
                                @endif
                            </div>
                        </div>
                        <div class="flex gap-2 pb-1">
                            @if($isOwn)
                                <a href="{{ route('profile.edit') }}" class="px-4 py-2 rounded-full border border-ama-ink/15 text-sm font-semibold text-ama-ink hover:bg-ama-canvas">{{ __('Editar perfil') }}</a>
                            @elseif($artist->isApproved())
                                @php $isFollowing = auth()->user()->isFollowing($artist); @endphp
                                <form method="POST" action="{{ route($isFollowing ? 'artists.unfollow' : 'artists.follow', $artist) }}">
                                    @csrf
                                    @if($isFollowing) @method('DELETE') @endif
                                    <button type="submit" class="px-5 py-2 rounded-full text-sm font-semibold transition {{ $isFollowing ? 'bg-ama-canvas text-ama-ink border border-ama-ink/15' : 'bg-ama-ink text-white hover:opacity-90' }}">
                                        {{ $isFollowing ? __('Siguiendo') : __('Seguir') }}
                                    </button>
                                </form>
                            @endif
                            @can('update', $artist)
                                <a href="{{ route('artists.edit', $artist) }}" class="px-4 py-2 rounded-full border border-ama-ink/15 text-sm font-semibold text-ama-ink hover:bg-ama-canvas">{{ __('Editar (admin)') }}</a>
                            @endcan
                        </div>
                    </div>

                    <h1 class="mt-3 text-2xl font-bold text-ama-ink">{{ $artist->displayName() }}</h1>
                    <p class="text-ama-muted">
                        {{ $artist->mainDiscipline?->name ?? __('Artista') }}
                        @if($artist->territory) · {{ $artist->territory->name }} @endif
                    </p>

                    <div class="mt-4 flex gap-8 text-sm">
                        <div><span class="font-bold text-ama-ink text-lg">{{ $artworks->count() }}</span> <span class="text-ama-muted">{{ __('Obras') }}</span></div>
                        <div><span class="font-bold text-ama-ink text-lg">{{ $artist->followers_count }}</span> <span class="text-ama-muted">{{ __('Seguidores') }}</span></div>
                        <div><span class="font-bold text-ama-ink text-lg">{{ $followingCount }}</span> <span class="text-ama-muted">{{ __('Siguiendo') }}</span></div>
                    </div>
                </div>
            </div>

            {{-- Tabs --}}
            <div x-data="{ tab: 'obras' }">
                <div class="flex gap-6 border-b border-ama-ink/10">
                    <button @click="tab = 'obras'" :class="tab === 'obras' ? 'border-ama-ink text-ama-ink' : 'border-transparent text-ama-muted'" class="pb-3 border-b-2 font-semibold text-sm">{{ __('Obras') }}</button>
                    <button @click="tab = 'sobre'" :class="tab === 'sobre' ? 'border-ama-ink text-ama-ink' : 'border-transparent text-ama-muted'" class="pb-3 border-b-2 font-semibold text-sm">{{ __('Sobre mí') }}</button>
                </div>

                <div x-show="tab === 'obras'" class="pt-5">
                    @if($artworks->isEmpty())
                        <p class="text-ama-muted">{{ __('Sin obras publicadas todavía.') }}</p>
                    @else
                        <div class="grid grid-cols-2 sm:grid-cols-3 gap-4">
                            @foreach($artworks as $artwork)
                                <a href="{{ route('artworks.show', $artwork) }}" class="block rounded-xl overflow-hidden border border-ama-ink/5 h-32 bg-ama-{{ $accentFor($artwork->id) }}/10">
                                    @if($artwork->cover)
                                        <img src="{{ $artwork->cover->thumbnailUrl() }}" alt="" class="w-full h-full object-cover">
                                    @else
                                        <div class="w-full h-full flex items-center justify-center text-ama-{{ $accentFor($artwork->id) }} font-bold">{{ $initials($artwork->title) }}</div>
                                    @endif
                                </a>
                            @endforeach
                        </div>
                    @endif
                </div>

                <div x-show="tab === 'sobre'" class="pt-5 space-y-4" style="display: none;">
                    <div class="bg-white rounded-xl border border-ama-ink/5 p-5">
                        <div class="prose max-w-none text-sm text-ama-ink whitespace-pre-line">
                            {{ $artist->bio_long ?? $artist->bio_short ?? __('Sin biografía registrada.') }}
                        </div>
                        @if($artist->website)
                            <a href="{{ $artist->website }}" target="_blank" class="mt-3 inline-block text-sm text-ama-teal hover:underline">{{ $artist->website }}</a>
                        @endif
                    </div>

                    @if($artist->disciplines->isNotEmpty())
                        <div class="flex flex-wrap gap-2">
                            @foreach($artist->disciplines as $discipline)
                                <span class="px-3 py-1 text-xs font-semibold rounded-full bg-ama-canvas text-ama-ink">{{ $discipline->name }}</span>
                            @endforeach
                        </div>
                    @endif
                </div>
            </div>

            @can('update', $artist)
                {{-- Admin / management details --}}
                <div class="bg-white rounded-xl border border-ama-ink/5 p-6">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-lg font-semibold text-ama-ink">{{ __('Panel de gestión') }}</h3>
                        <span class="text-sm text-ama-muted">{{ __('Estado') }}: <strong class="text-ama-ink">{{ __("status.{$artist->status}") }}</strong></span>
                    </div>
                    <dl class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
                        <div><dt class="text-ama-muted">{{ __('Nombre legal') }}</dt><dd class="text-ama-ink">{{ $artist->legal_name ?? '-' }}</dd></div>
                        <div><dt class="text-ama-muted">{{ __('RUT / Documento') }}</dt><dd class="text-ama-ink">{{ $artist->document_number ?? '-' }}</dd></div>
                        <div><dt class="text-ama-muted">{{ __('Email de contacto') }}</dt><dd class="text-ama-ink">{{ $artist->email_contact ?? '-' }}</dd></div>
                        <div><dt class="text-ama-muted">{{ __('Teléfono') }}</dt><dd class="text-ama-ink">{{ $artist->phone ?? '-' }}</dd></div>
                        <div class="md:col-span-2"><dt class="text-ama-muted">{{ __('Dirección') }}</dt><dd class="text-ama-ink">{{ $artist->address ?? '-' }}</dd></div>
                    </dl>
                </div>
            @endcan

            @can('wordpress.publish')
                <div class="bg-white rounded-xl border border-ama-ink/5 p-6">
                    <div class="flex flex-wrap items-start justify-between gap-4">
                        <div>
                            <h3 class="text-lg font-semibold text-ama-ink">{{ __('Publicación WordPress') }}</h3>
                            <p class="mt-1 text-sm text-ama-muted">
                                {{ __('Estado:') }} {{ $artist->wordpressPublication?->status ?? __('sin publicar') }}
                                @if($artist->wordpressPublication?->wordpress_url)
                                    · <a href="{{ $artist->wordpressPublication->wordpress_url }}" target="_blank" class="text-ama-teal hover:underline">{{ __('Ver en WordPress') }}</a>
                                @endif
                            </p>
                            @if($artist->wordpressPublication?->last_error)
                                <p class="mt-2 text-sm text-ama-coral">{{ $artist->wordpressPublication->last_error }}</p>
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

            @can('delete', $artist)
                <div class="bg-white rounded-xl border border-ama-coral/20 p-6">
                    <h3 class="text-lg font-semibold text-ama-coral mb-4">{{ __('Zona de peligro') }}</h3>
                    <form method="POST" action="{{ route('artists.destroy', $artist) }}" onsubmit="return confirm('{{ __('¿Estás seguro de eliminar este artista?') }}')">
                        @csrf
                        @method('DELETE')
                        <x-danger-button>{{ __('Eliminar artista') }}</x-danger-button>
                    </form>
                </div>
            @endcan
        </div>
    </div>
</x-app-layout>
