<h2>{{ $artist->displayName() }}</h2>

@if($artist->mainDiscipline)
    <p><strong>Disciplina:</strong> {{ $artist->mainDiscipline->name }}</p>
@endif

@if($artist->territory || $artist->commune)
    <p><strong>Comuna:</strong> {{ $artist->territory?->name ?? $artist->commune }}</p>
@endif

@if($artist->bio_long)
    {!! nl2br(e($artist->bio_long)) !!}
@elseif($artist->bio_short)
    <p>{{ $artist->bio_short }}</p>
@endif

@if($artist->website)
    <p><a href='{{ $artist->website }}'>Sitio web</a></p>
@endif
