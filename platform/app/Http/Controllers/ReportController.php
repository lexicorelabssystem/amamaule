<?php

namespace App\Http\Controllers;

use App\Models\Artist;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ReportController extends Controller
{
    public function index(Request $request): View
    {
        abort_unless($request->user()->can('exports.create'), 403);
        $byTerritory = Artist::query()->leftJoin('territories', 'artists.territory_id', '=', 'territories.id')
            ->selectRaw('COALESCE(territories.name, artists.commune, \'Sin comuna\') as label, COUNT(artists.id) as total')
            ->whereNull('artists.deleted_at')->groupBy('territories.name', 'artists.commune')->orderByDesc('total')->limit(15)->get();
        $byDiscipline = Artist::query()->leftJoin('disciplines', 'artists.main_discipline_id', '=', 'disciplines.id')
            ->selectRaw('COALESCE(disciplines.name, \'Sin disciplina\') as label, COUNT(artists.id) as total')
            ->whereNull('artists.deleted_at')->groupBy('disciplines.name')->orderByDesc('total')->limit(15)->get();
        $max = max(1, (int) $byTerritory->max('total'), (int) $byDiscipline->max('total'));

        return view('reports.index', compact('byTerritory', 'byDiscipline', 'max'));
    }
}
