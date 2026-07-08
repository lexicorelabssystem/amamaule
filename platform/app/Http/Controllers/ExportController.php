<?php

namespace App\Http\Controllers;

use App\Models\Activity;
use App\Models\Artist;
use App\Services\SpreadsheetExportService;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ExportController extends Controller
{
    public function artists(Request $request, SpreadsheetExportService $exporter): StreamedResponse
    {
        abort_unless($request->user()->can('exports.create'), 403);
        $query = Artist::with(['territory', 'mainDiscipline'])->latest();
        $query->when($request->filled('status'), fn ($q) => $q->where('status', $request->input('status')))
            ->when($request->filled('territory_id'), fn ($q) => $q->where('territory_id', $request->integer('territory_id')))
            ->when($request->filled('main_discipline_id'), fn ($q) => $q->where('main_discipline_id', $request->integer('main_discipline_id')))
            ->when($request->filled('search'), fn ($q) => $q->where(fn ($s) => $s->where('public_name', 'like', '%'.$request->input('search').'%')->orWhere('legal_name', 'like', '%'.$request->input('search').'%')));
        $rows = $query->cursor()->map(fn (Artist $artist) => [$artist->public_name, $artist->legal_name, $artist->email_contact, $artist->commune, $artist->territory?->name, $artist->mainDiscipline?->name, $artist->status]);

        return $exporter->download(['Nombre publico', 'Nombre legal', 'Email', 'Comuna', 'Territorio', 'Disciplina', 'Estado'], $rows, 'artistas-'.now()->format('Ymd-His'), $request->input('format', 'xlsx'));
    }

    public function activities(Request $request, SpreadsheetExportService $exporter): StreamedResponse
    {
        abort_unless($request->user()->can('exports.create'), 403);
        $query = Activity::with(['artist', 'territory'])->latest();
        $query->when($request->filled('status'), fn ($q) => $q->where('status', $request->input('status')))
            ->when($request->filled('territory_id'), fn ($q) => $q->where('territory_id', $request->integer('territory_id')))
            ->when($request->filled('search'), fn ($q) => $q->where('title', 'like', '%'.$request->input('search').'%'));
        $rows = $query->cursor()->map(fn (Activity $activity) => [$activity->title, $activity->artist->public_name, $activity->territory?->name, $activity->location, $activity->start_date?->format('Y-m-d H:i'), $activity->status]);

        return $exporter->download(['Titulo', 'Artista', 'Comuna', 'Lugar', 'Fecha', 'Estado'], $rows, 'actividades-'.now()->format('Ymd-His'), $request->input('format', 'xlsx'));
    }
}
