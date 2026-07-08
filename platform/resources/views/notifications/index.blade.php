<x-app-layout><div class='py-12'><div class='max-w-4xl mx-auto p-6 bg-white dark:bg-gray-800'>
 <div class='flex justify-between'><h1 class='text-xl'>Notificaciones</h1><form method='POST' action='{{ route('notifications.read-all') }}'>@csrf @method('PATCH')<button>Marcar todas como leidas</button></form></div>
 @forelse($notifications as $notification)<form method='POST' action='{{ route('notifications.read',$notification->id) }}' class='p-3 border-b'>@csrf @method('PATCH')<button class='text-left @if(!$notification->read_at) font-bold @endif'>{{ $notification->data['title'] ?? 'Notificacion' }} - {{ $notification->data['status'] ?? '' }}</button></form>@empty<p>No hay notificaciones.</p>@endforelse
 {{ $notifications->links() }}
</div></div></x-app-layout>
