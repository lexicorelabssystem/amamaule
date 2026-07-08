@csrf
<div class='space-y-4'>
 <div><label for='title'>Titulo</label><input id='title' name='title' class='block w-full' value='{{ old('title', $proposal->title ?? null) }}' required></div>
 <div><label for='activity_id'>Actividad relacionada</label><select id='activity_id' name='activity_id' class='block w-full'><option value=''>Ninguna</option>@foreach($activities as $id => $title)<option value='{{ $id }}' @selected(old('activity_id', $proposal->activity_id ?? null) == $id)>{{ $title }}</option>@endforeach</select></div>
 <div><label for='description'>Descripcion</label><textarea id='description' name='description' class='block w-full' required>{{ old('description', $proposal->description ?? null) }}</textarea></div>
 <div><label for='objectives'>Objetivos</label><textarea id='objectives' name='objectives' class='block w-full'>{{ old('objectives', $proposal->objectives ?? null) }}</textarea></div>
 <div><label for='target_audience'>Publico objetivo</label><textarea id='target_audience' name='target_audience' class='block w-full'>{{ old('target_audience', $proposal->target_audience ?? null) }}</textarea></div>
 <div><label for='requirements'>Requerimientos</label><textarea id='requirements' name='requirements' class='block w-full'>{{ old('requirements', $proposal->requirements ?? null) }}</textarea></div>
 <div><label for='budget'>Presupuesto</label><input id='budget' name='budget' type='number' min='0' step='0.01' value='{{ old('budget', $proposal->budget ?? null) }}'></div>
 @if($errors->any())<ul class='text-red-600'>@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>@endif
</div>
