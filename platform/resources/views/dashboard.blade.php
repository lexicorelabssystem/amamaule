<x-app-layout><div class='py-12'><div class='max-w-7xl mx-auto space-y-6'>
 <section class='p-6 bg-white dark:bg-gray-800'><h1 class='text-xl'>Bienvenido, {{ auth()->user()->name }}</h1><p>Notificaciones sin leer: {{ auth()->user()->unreadNotifications()->count() }}</p><a href='{{ route('notifications.index') }}'>Ver notificaciones</a></section>
 @if($metrics)
 <section class='grid grid-cols-1 md:grid-cols-5 gap-4'>@foreach($metrics as $name => $value)<div class='p-5 bg-white dark:bg-gray-800'><p class='text-sm'>{{ str_replace('_',' ',$name) }}</p><strong class='text-2xl'>{{ $value }}</strong></div>@endforeach</section>
 <section class='p-6 bg-white dark:bg-gray-800'><h2 class='text-lg'>Actividad reciente</h2>@forelse($audits as $audit)<p>{{ $audit->created_at->format('d/m/Y H:i') }} - {{ $audit->event }} - {{ $audit->user?->name ?? 'Sistema' }}</p>@empty<p>Sin actividad registrada.</p>@endforelse</section>
 @endif
</div></div></x-app-layout>
