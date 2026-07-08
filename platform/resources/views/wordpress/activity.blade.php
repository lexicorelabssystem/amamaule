<h2>{{ $activity->title }}</h2>

<p><strong>Artista:</strong> {{ $activity->artist?->displayName() }}</p>

@if($activity->start_date)
    <p><strong>Fecha:</strong> {{ $activity->start_date->format('d/m/Y H:i') }}</p>
@endif

@if($activity->territory || $activity->location)
    <p><strong>Lugar:</strong> {{ $activity->location }} {{ $activity->territory?->name ? '? '.$activity->territory->name : '' }}</p>
@endif

@if($activity->description)
    {!! nl2br(e($activity->description)) !!}
@elseif($activity->short_description)
    <p>{{ $activity->short_description }}</p>
@endif
