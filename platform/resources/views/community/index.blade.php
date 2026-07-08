<x-app-layout>
    <x-slot name='header'>
        <h2 class='font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight'>{{ __('Comunidad') }}</h2>
    </x-slot>

    <div class='py-12'>
        <div class='max-w-7xl mx-auto sm:px-6 lg:px-8'>
            <div class='bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg p-6'>
                @forelse($channels as $channel)
                    <article class='py-4 border-b border-gray-200 dark:border-gray-700 last:border-b-0'>
                        <a class='text-lg font-semibold text-indigo-600 dark:text-indigo-400 hover:underline' href='{{ route('community.channels.show', $channel) }}'>{{ $channel->name }}</a>
                        <p class='text-sm text-gray-600 dark:text-gray-400'>{{ $channel->discipline?->name ?? __('General') }} ? {{ $channel->visible_messages_count }} {{ __('mensajes') }}</p>
                        @if($channel->description)
                            <p class='mt-2 text-gray-700 dark:text-gray-300'>{{ $channel->description }}</p>
                        @endif
                    </article>
                @empty
                    <p class='text-gray-600 dark:text-gray-400'>{{ __('A?n no hay canales activos.') }}</p>
                @endforelse

                <div class='mt-4'>{{ $channels->links() }}</div>
            </div>
        </div>
    </div>
</x-app-layout>
