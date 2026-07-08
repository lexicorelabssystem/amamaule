<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreActivityRequest;
use App\Http\Requests\UpdateActivityRequest;
use App\Models\Activity;
use App\Models\Artist;
use App\Models\Territory;
use App\Models\User;
use App\Services\AuditService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ActivityController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorize('viewAny', Activity::class);

        $query = Activity::with(['artist', 'territory', 'cover'])
            ->when(! $request->user()->can('activities.view_any'), function ($query) use ($request) {
                $artistIds = $request->user()->artist?->id
                    ? [$request->user()->artist->id]
                    : [];
                $query->whereIn('artist_id', $artistIds);
            })
            ->when($request->filled('status'), fn ($query) => $query->where('status', $request->string('status')))
            ->when($request->filled('territory_id'), fn ($query) => $query->where('territory_id', $request->integer('territory_id')))
            ->when($request->filled('search'), function ($query) use ($request) {
                $term = $request->string('search');
                $query->where(fn ($q) => $q->where('title', 'like', '%'.$term.'%')->orWhere('location', 'like', '%'.$term.'%'));
            })
            ->latest();

        $activities = $query->paginate(15)->withQueryString();

        return view('activities.index', ['activities' => $activities, 'statuses' => Activity::$statuses, 'territories' => Territory::orderBy('name')->pluck('name', 'id')]);
    }

    public function create(Request $request): View
    {
        $this->authorize('create', Activity::class);

        return view('activities.create', [
            'artists' => $this->availableArtists($request->user()),
            'territories' => Territory::orderBy('name')->pluck('name', 'id'),
            'statuses' => Activity::$statuses,
            'userArtist' => $request->user()->artist,
        ]);
    }

    public function store(StoreActivityRequest $request): RedirectResponse
    {
        $this->authorize('create', Activity::class);

        $artist = $this->resolveArtist($request);

        abort_if($artist === null, 403, 'No tienes un perfil de artista asociado.');

        $data = $request->validated();
        $data['artist_id'] = $artist->id;
        $data['status'] = Activity::STATUS_DRAFT;
        $data['created_by'] = $request->user()->id;
        $data['updated_by'] = $request->user()->id;

        $activity = Activity::create($data);

        return redirect()
            ->route('activities.show', $activity)
            ->with('success', 'Actividad creada correctamente.');
    }

    public function show(Activity $activity): View
    {
        $this->authorize('view', $activity);

        $activity->load(['artist', 'territory', 'media', 'cover', 'createdBy', 'updatedBy']);

        return view('activities.show', compact('activity'));
    }

    public function edit(Request $request, Activity $activity): View
    {
        $this->authorize('update', $activity);

        return view('activities.edit', [
            'activity' => $activity,
            'artists' => $this->availableArtists($request->user()),
            'territories' => Territory::orderBy('name')->pluck('name', 'id'),
            'statuses' => Activity::$statuses,
            'userArtist' => $request->user()->artist,
        ]);
    }

    public function update(UpdateActivityRequest $request, Activity $activity): RedirectResponse
    {
        $this->authorize('update', $activity);

        $data = $request->validated();
        $data['updated_by'] = $request->user()->id;

        $activity->update($data);

        return redirect()
            ->route('activities.show', $activity)
            ->with('success', 'Actividad actualizada correctamente.');
    }

    public function destroy(Activity $activity): RedirectResponse
    {
        $this->authorize('delete', $activity);

        $activity->delete();

        return redirect()
            ->route('activities.index')
            ->with('success', 'Actividad eliminada correctamente.');
    }

    public function publish(Activity $activity): RedirectResponse
    {
        $this->authorize('publish', $activity);

        $oldStatus = $activity->status;
        $activity->update(['status' => Activity::STATUS_PUBLISHED]);
        app(AuditService::class)->record('activity.published', $activity, ['status' => $oldStatus], ['status' => Activity::STATUS_PUBLISHED]);

        return redirect()
            ->route('activities.show', $activity)
            ->with('success', 'Actividad publicada.');
    }

    public function archive(Activity $activity): RedirectResponse
    {
        $this->authorize('archive', $activity);

        $oldStatus = $activity->status;
        $activity->update(['status' => Activity::STATUS_ARCHIVED]);
        app(AuditService::class)->record('activity.archived', $activity, ['status' => $oldStatus], ['status' => Activity::STATUS_ARCHIVED]);

        return redirect()
            ->route('activities.show', $activity)
            ->with('success', 'Actividad archivada.');
    }

    protected function availableArtists(User $user): array
    {
        if ($user->can('activities.view_any')) {
            return Artist::orderBy('public_name')->pluck('public_name', 'id')->toArray();
        }

        if ($user->artist) {
            return [$user->artist->id => $user->artist->public_name];
        }

        return [];
    }

    protected function resolveArtist(Request $request): ?Artist
    {
        if ($request->user()->can('activities.view_any') && $request->filled('artist_id')) {
            return Artist::find($request->input('artist_id'));
        }

        return $request->user()->artist;
    }
}
