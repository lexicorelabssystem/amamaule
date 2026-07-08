<x-app-layout><div class='py-12'><div class='max-w-4xl mx-auto p-6 bg-white dark:bg-gray-800 space-y-5'>
 @if(session('success'))<p class='text-green-600'>{{ session('success') }}</p>@endif
 <h1 class='text-2xl dark:text-white'>{{ $proposal->title }}</h1><p>{{ $proposal->status }} @if($proposal->score) - Puntaje: {{ $proposal->score }}@endif</p><p>{{ $proposal->description }}</p>
 @can('update',$proposal)<a href='{{ route('proposals.edit',$proposal) }}'>Editar</a>@endcan
 @can('submit',$proposal) @if($proposal->isDraft() || $proposal->status === 'needs_changes')<form method='POST' action='{{ route('proposals.submit',$proposal) }}'>@csrf @method('PATCH')<button>Enviar a revision</button></form>@endif @endcan
 @can('review',$proposal) @if($proposal->status === 'submitted')<form method='POST' action='{{ route('proposal-reviews.start',$proposal) }}'>@csrf @method('PATCH')<button>Iniciar revision</button></form>@endif @endcan
 @if(in_array($proposal->status, ['submitted','in_review']))
  @can('approve',$proposal)<form method='POST' action='{{ route('proposal-reviews.approve',$proposal) }}'>@csrf @method('PATCH')<input name='score' type='number' min='1' max='100' placeholder='Puntaje'><textarea name='comment' placeholder='Comentario'></textarea><button>Aprobar</button></form>@endcan
  @can('reject',$proposal)<form method='POST' action='{{ route('proposal-reviews.reject',$proposal) }}'>@csrf @method('PATCH')<textarea name='comment' required placeholder='Motivo del rechazo'></textarea><button>Rechazar</button></form>@endcan
  @can('requestChanges',$proposal)<form method='POST' action='{{ route('proposal-reviews.request-changes',$proposal) }}'>@csrf @method('PATCH')<textarea name='comment' required placeholder='Cambios requeridos'></textarea><button>Solicitar cambios</button></form>@endcan
 @endif
 @can('comments.view_internal')
  <h2>Comentarios internos</h2>@forelse($proposal->comments as $comment)<p>{{ $comment->user->name }}: {{ $comment->body }}</p>@empty<p>Sin comentarios internos.</p>@endforelse
  @can('review',$proposal)<form method='POST' action='{{ route('comments.store') }}'>@csrf<input type='hidden' name='commentable_type' value='App\Models\Proposal'><input type='hidden' name='commentable_id' value='{{ $proposal->id }}'><textarea name='body' required></textarea><button>Comentario interno</button></form>@endcan
 @endcan
 <h2>Historial</h2>@forelse($proposal->reviews as $review)<p>{{ $review->old_status }} → {{ $review->new_status }} - {{ $review->user->name }} {{ $review->comment }}</p>@empty<p>Sin revisiones.</p>@endforelse
 </div></div></x-app-layout>
