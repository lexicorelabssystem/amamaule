<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ __('Dashboard') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900 dark:text-gray-100">
                    <p class="text-lg font-medium">
                        {{ __('Bienvenido, :name', ['name' => Auth::user()->name]) }}
                    </p>
                    <p class="mt-2 text-sm text-gray-600 dark:text-gray-400">
                        {{ __('Rol: :role', ['role' => Auth::user()->roles->pluck('name')->first() ?? 'Sin rol']) }}
                    </p>
                </div>
            </div>

            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900 dark:text-gray-100">
                    <h3 class="font-semibold text-lg mb-2">{{ __('Estado de la plataforma') }}</h3>
                    <p class="text-sm text-gray-600 dark:text-gray-400">
                        {{ __('Plataforma AMA está funcionando correctamente. WordPress sigue como vitrina pública.') }}
                    </p>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
