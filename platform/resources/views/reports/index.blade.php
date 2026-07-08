<x-app-layout>
    <x-slot name='header'>
        <h2 class='font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight'>
            {{ __('Reportes administrativos') }}
        </h2>
    </x-slot>

    <div class='py-12'>
        <div class='max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6'>
            @foreach(['Artistas por comuna' => $byTerritory, 'Artistas por disciplina' => $byDiscipline] as $title => $items)
                <section class='bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg p-6'>
                    <h3 class='text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4'>{{ __($title) }}</h3>

                    @forelse($items as $item)
                        <div class='mb-4'>
                            <div class='flex items-center justify-between text-sm text-gray-700 dark:text-gray-300 mb-1'>
                                <span>{{ $item->label }}</span>
                                <strong>{{ $item->total }}</strong>
                            </div>
                            <div class='h-3 bg-gray-200 dark:bg-gray-700 rounded-full overflow-hidden'>
                                <div class='h-3 bg-indigo-600 rounded-full' style='width: {{ round(($item->total / $max) * 100) }}%'></div>
                            </div>
                        </div>
                    @empty
                        <p class='text-sm text-gray-600 dark:text-gray-400'>{{ __('Sin datos para mostrar.') }}</p>
                    @endforelse
                </section>
            @endforeach
        </div>
    </div>
</x-app-layout>
