<x-app-layout>
    <x-slot name='header'>
        <div class='flex items-center justify-between'>
            <h2 class='font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight'>{{ $channel->name }}</h2>
            <a class='text-sm text-indigo-600 dark:text-indigo-400 hover:underline' href='{{ route('community.channels.index') }}'>{{ __('Volver a comunidad') }}</a>
        </div>
    </x-slot>

    <div class='py-12'>
        <div class='max-w-4xl mx-auto sm:px-6 lg:px-8 space-y-6'>
            @if(session('status'))
                <div class='bg-green-50 dark:bg-green-900/30 text-green-700 dark:text-green-200 p-4 rounded'>{{ session('status') }}</div>
            @endif

            <section class='bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg p-6'>
                <p class='text-sm text-gray-600 dark:text-gray-400'>{{ $channel->discipline?->name ?? __('Canal general') }}</p>
                @if($channel->description)
                    <p class='mt-2 text-gray-700 dark:text-gray-300'>{{ $channel->description }}</p>
                @endif
            </section>

            @can('community.message')
                <section class='bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg p-6'>
                    <form method='POST' action='{{ route('community.messages.store', $channel) }}' class='space-y-4'>
                        @csrf
                        <div>
                            <x-input-label for='body' :value="__('Nuevo mensaje')" />
                            <textarea id='body' name='body' rows='3' required class='mt-1 block w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 rounded-md shadow-sm'>{{ old('body') }}</textarea>
                            <x-input-error :messages="$errors->get('body')" class='mt-2' />
                        </div>
                        <x-primary-button>{{ __('Publicar') }}</x-primary-button>
                    </form>
                </section>
            @endcan

            <section class='bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg p-6'>
                @forelse($messages as $message)
                    <article class='py-4 border-b border-gray-200 dark:border-gray-700 last:border-b-0'>
                        <div class='flex items-center justify-between gap-4'>
                            <p class='text-sm font-semibold text-gray-900 dark:text-gray-100'>{{ $message->user->name }}</p>
                            <p class='text-xs text-gray-500'>{{ $message->created_at->diffForHumans() }}</p>
                        </div>
                        <p class='mt-2 whitespace-pre-line text-gray-700 dark:text-gray-300'>{{ $message->body }}</p>
                        <form method='POST' action='{{ route('moderation-reports.store', $message) }}' class='mt-3 flex flex-wrap gap-2 items-start'>
                            @csrf
                            <input name='reason' placeholder='Motivo del reporte' class='text-sm border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 rounded-md' required>
                            <button class='text-xs text-red-600 dark:text-red-400 hover:underline'>{{ __('Reportar') }}</button>
                        </form>
                    </article>
                @empty
                    <p class='text-gray-600 dark:text-gray-400'>{{ __('A?n no hay mensajes en este canal.') }}</p>
                @endforelse

                <div class='mt-4'>{{ $messages->links() }}</div>
            </section>
        </div>
    </div>
</x-app-layout>
