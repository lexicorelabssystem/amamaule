<x-app-layout>
 <div class='py-12'><div class='max-w-7xl mx-auto p-6 bg-white dark:bg-gray-800'>
 <a href='{{ route('proposals.create') }}'>Nueva propuesta</a>
  <form method='GET' class='flex gap-2 my-4'><input name='search' value='{{ request('search') }}' placeholder='Buscar'><select name='status'><option value=''>Todos los estados</option>@foreach($statuses as $status)<option value='{{ $status }}' @selected(request('status')===$status)>{{ $status }}</option>@endforeach</select><button>Filtrar</button></form>
  <form method='GET' class='flex gap-2 my-4'><input name='search' value='{{ request('search') }}' placeholder='Buscar'><select name='status'><option value=''>Todos los estados</option>@foreach($statuses as $status)<option value='{{ $status }}' @selected(request('status')===$status)>{{ $status }}</option>@endforeach</select><button>Filtrar</button></form>
  @forelse($proposals as $proposal)
   <p><a href='{{ route('proposals.show', $proposal) }}'>{{ $proposal->title }}</a> - {{ $proposal->status }}</p>
  @empty <p>No hay propuestas.</p> @endforelse
  {{ $proposals->links() }}
 </div></div>
</x-app-layout>
