<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ __('Importar artistas') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg p-6">
                <p class="text-gray-600 dark:text-gray-400 mb-6">
                    {{ __('Sube un archivo CSV o Excel con las columnas obligatorias:') }}
                    <code class="text-sm bg-gray-100 dark:bg-gray-700 px-1 py-0.5 rounded">legal_name</code>
                    {{ __('y') }}
                    <code class="text-sm bg-gray-100 dark:bg-gray-700 px-1 py-0.5 rounded">email_contact</code>.
                </p>

                @if(session('error'))
                    <div class="mb-4 p-4 bg-red-50 dark:bg-red-900/20 text-red-700 dark:text-red-300 rounded-md">
                        {{ session('error') }}
                    </div>
                @endif

                <form method="POST" action="{{ route('imports.store') }}" enctype="multipart/form-data" class="space-y-6">
                    @csrf

                    <div>
                        <x-input-label for="file" :value="__('Archivo')" />
                        <input
                            id="file"
                            name="file"
                            type="file"
                            accept=".csv,.xlsx,.xls"
                            class="mt-1 block w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-indigo-500 dark:focus:border-indigo-600 focus:ring-indigo-500 dark:focus:ring-indigo-600 rounded-md shadow-sm"
                            required
                        />
                        <x-input-error :messages="$errors->get('file')" class="mt-2" />
                    </div>

                    <div class="flex items-center gap-4">
                        <x-primary-button>{{ __('Cargar y previsualizar') }}</x-primary-button>
                        <a href="{{ route('imports.index') }}" class="text-sm text-gray-600 dark:text-gray-400 hover:underline">
                            {{ __('Ver historial') }}
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
