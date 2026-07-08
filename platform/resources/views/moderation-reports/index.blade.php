<x-app-layout>
    <x-slot name='header'>
        <h2 class='font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight'>{{ __('Moderaci?n') }}</h2>
    </x-slot>

    <div class='py-12'>
        <div class='max-w-7xl mx-auto sm:px-6 lg:px-8'>
            <div class='bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg p-6'>
                @if(session('status'))
                    <div class='mb-4 bg-green-50 dark:bg-green-900/30 text-green-700 dark:text-green-200 p-4 rounded'>{{ session('status') }}</div>
                @endif

                @forelse($reports as $report)
                    <article class='py-4 border-b border-gray-200 dark:border-gray-700 last:border-b-0'>
                        <div class='flex items-start justify-between gap-4'>
                            <div>
                                <p class='font-semibold text-gray-900 dark:text-gray-100'>{{ $report->reason }} ? {{ $report->status }}</p>
                                <p class='text-sm text-gray-600 dark:text-gray-400'>{{ __('Reportado por') }} {{ $report->reporter->name }} ? {{ $report->created_at->diffForHumans() }}</p>
                                @if($report->reportable)
                                    <p class='mt-2 text-gray-700 dark:text-gray-300'>{{ $report->reportable->body }}</p>
                                @endif
                                @if($report->details)
                                    <p class='mt-2 text-sm text-gray-600 dark:text-gray-400'>{{ $report->details }}</p>
                                @endif
                            </div>
                            @if($report->status === 'open')
                                <form method='POST' action='{{ route('moderation-reports.resolve', $report) }}' class='flex items-center gap-2'>
                                    @csrf
                                    @method('PATCH')
                                    <label class='text-sm text-gray-600 dark:text-gray-400'><input type='checkbox' name='hide_content' value='1'> {{ __('Ocultar') }}</label>
                                    <x-primary-button>{{ __('Resolver') }}</x-primary-button>
                                </form>
                            @endif
                        </div>
                    </article>
                @empty
                    <p class='text-gray-600 dark:text-gray-400'>{{ __('No hay reportes de moderaci?n.') }}</p>
                @endforelse

                <div class='mt-4'>{{ $reports->links() }}</div>
            </div>
        </div>
    </div>
</x-app-layout>
