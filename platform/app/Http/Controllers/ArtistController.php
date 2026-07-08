<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreArtistRequest;
use App\Http\Requests\UpdateArtistRequest;
use App\Models\Artist;
use App\Models\Discipline;
use App\Models\Territory;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class ArtistController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorize('viewAny', Artist::class);

        $query = Artist::with(['territory', 'mainDiscipline', 'user'])
            ->orderBy('created_at', 'desc');

        if (! Auth::user()->can('artists.view_any')) {
            $query->where('user_id', Auth::id());
        }

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        if ($request->filled('territory_id')) {
            $query->where('territory_id', $request->input('territory_id'));
        }

        if ($request->filled('main_discipline_id')) {
            $query->where('main_discipline_id', $request->input('main_discipline_id'));
        }

        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('legal_name', 'like', "%{$search}%")
                    ->orWhere('public_name', 'like', "%{$search}%")
                    ->orWhere('artistic_name', 'like', "%{$search}%")
                    ->orWhere('email_contact', 'like', "%{$search}%");
            });
        }

        $artists = $query->paginate(20)->withQueryString();

        return view('artists.index', [
            'artists' => $artists,
            'statuses' => Artist::$statuses,
            'territories' => Territory::orderBy('name')->pluck('name', 'id'),
            'disciplines' => Discipline::orderBy('name')->pluck('name', 'id'),
        ]);
    }

    public function create(): View
    {
        $this->authorize('create', Artist::class);

        return view('artists.create', [
            'territories' => Territory::orderBy('name')->get(),
            'disciplines' => Discipline::orderBy('name')->get(),
            'statuses' => Artist::$statuses,
        ]);
    }

    public function store(StoreArtistRequest $request): RedirectResponse
    {
        $this->authorize('create', Artist::class);

        $data = $request->validated();
        $data['created_by'] = Auth::id();
        $data['updated_by'] = Auth::id();

        $disciplineIds = $data['disciplines'] ?? [];
        unset($data['disciplines']);

        $artist = Artist::create($data);

        if (! empty($disciplineIds)) {
            $syncData = collect($disciplineIds)->mapWithKeys(function ($id) use ($data) {
                return [$id => ['is_primary' => $id == ($data['main_discipline_id'] ?? null)]];
            })->toArray();

            $artist->disciplines()->sync($syncData);
        }

        return redirect()->route('artists.show', $artist)
            ->with('status', 'Artista creado correctamente.');
    }

    public function show(Artist $artist): View
    {
        $this->authorize('view', $artist);

        return view('artists.show', [
            'artist' => $artist->load(['territory', 'mainDiscipline', 'disciplines', 'user', 'approvedBy', 'createdBy']),
        ]);
    }

    public function edit(Artist $artist): View
    {
        $this->authorize('update', $artist);

        return view('artists.edit', [
            'artist' => $artist->load('disciplines'),
            'territories' => Territory::orderBy('name')->get(),
            'disciplines' => Discipline::orderBy('name')->get(),
            'statuses' => Artist::$statuses,
        ]);
    }

    public function update(UpdateArtistRequest $request, Artist $artist): RedirectResponse
    {
        $this->authorize('update', $artist);

        $data = $request->validated();
        $data['updated_by'] = Auth::id();

        $disciplineIds = $data['disciplines'] ?? [];
        unset($data['disciplines']);

        $artist->update($data);

        if (! empty($disciplineIds)) {
            $syncData = collect($disciplineIds)->mapWithKeys(function ($id) use ($data) {
                return [$id => ['is_primary' => $id == ($data['main_discipline_id'] ?? null)]];
            })->toArray();

            $artist->disciplines()->sync($syncData);
        } else {
            $artist->disciplines()->detach();
        }

        return redirect()->route('artists.show', $artist)
            ->with('status', 'Artista actualizado correctamente.');
    }

    public function destroy(Artist $artist): RedirectResponse
    {
        $this->authorize('delete', $artist);

        $artist->delete();

        return redirect()->route('artists.index')
            ->with('status', 'Artista eliminado correctamente.');
    }
}
