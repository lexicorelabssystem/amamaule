<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ __('Nueva actividad') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg p-6">
                <form method="POST" action="{{ route('activities.store') }}" class="space-y-6">
                    @include('activities._form', ['activity' => null])

                    <div class="flex items-center gap-4">
                        <x-primary-button>{{ __('Guardar borrador') }}</x-primary-button>
                        <a href="{{ route('activities.index') }}" class="text-sm text-gray-600 dark:text-gray-400 hover:underline">{{ __('Cancelar') }}</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
