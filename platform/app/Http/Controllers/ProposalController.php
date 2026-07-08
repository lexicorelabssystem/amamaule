<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreProposalRequest;
use App\Http\Requests\UpdateProposalRequest;
use App\Models\Activity;
use App\Models\Proposal;
use App\Models\Review;
use App\Models\User;
use App\Services\AuditService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ProposalController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorize('viewAny', Proposal::class);

        $proposals = Proposal::with(['artist', 'activity'])
            ->when(! $request->user()->can('proposals.view_any'), function ($query) use ($request) {
                $artistIds = $request->user()->artist?->id
                    ? [$request->user()->artist->id]
                    : [];
                $query->whereIn('artist_id', $artistIds);
            })
            ->when($request->filled('status'), fn ($query) => $query->where('status', $request->string('status')))
            ->when($request->filled('search'), function ($query) use ($request) {
                $term = $request->string('search');
                $query->where(fn ($q) => $q->where('title', 'like', '%'.$term.'%')->orWhere('description', 'like', '%'.$term.'%'));
            })
            ->latest()
            ->paginate(15)->withQueryString();

        return view('proposals.index', ['proposals' => $proposals, 'statuses' => Proposal::$statuses]);
    }

    public function create(Request $request): View
    {
        $this->authorize('create', Proposal::class);

        return view('proposals.create', [
            'activities' => $this->availableActivities($request->user()),
        ]);
    }

    public function store(StoreProposalRequest $request): RedirectResponse
    {
        $this->authorize('create', Proposal::class);

        $artist = $request->user()->artist;

        abort_if($artist === null, 403, 'No tienes un perfil de artista asociado.');

        $data = $request->validated();
        $this->ensureActivityBelongsToArtist($data['activity_id'] ?? null, $artist->id);
        $data['artist_id'] = $artist->id;
        $data['status'] = Proposal::STATUS_DRAFT;
        $data['created_by'] = $request->user()->id;
        $data['updated_by'] = $request->user()->id;

        $proposal = Proposal::create($data);
        app(AuditService::class)->record('proposal.created', $proposal, [], $proposal->only(['title', 'status', 'artist_id']));

        return redirect()
            ->route('proposals.show', $proposal)
            ->with('success', 'Propuesta creada correctamente.');
    }

    public function show(Proposal $proposal): View
    {
        $this->authorize('view', $proposal);

        $proposal->load(['artist', 'activity', 'comments.user', 'reviews.user', 'approvedBy']);

        return view('proposals.show', compact('proposal'));
    }

    public function edit(Request $request, Proposal $proposal): View
    {
        $this->authorize('update', $proposal);

        return view('proposals.edit', [
            'proposal' => $proposal,
            'activities' => $this->availableActivities($request->user()),
        ]);
    }

    public function update(UpdateProposalRequest $request, Proposal $proposal): RedirectResponse
    {
        $this->authorize('update', $proposal);

        $data = $request->validated();
        $this->ensureActivityBelongsToArtist($data['activity_id'] ?? null, $proposal->artist_id);
        $data['updated_by'] = $request->user()->id;

        $proposal->update($data);

        return redirect()
            ->route('proposals.show', $proposal)
            ->with('success', 'Propuesta actualizada correctamente.');
    }

    public function destroy(Proposal $proposal): RedirectResponse
    {
        $this->authorize('delete', $proposal);

        $proposal->delete();

        return redirect()
            ->route('proposals.index')
            ->with('success', 'Propuesta eliminada correctamente.');
    }

    public function submit(Request $request, Proposal $proposal): RedirectResponse
    {
        $this->authorize('submit', $proposal);

        if (! in_array($proposal->status, [Proposal::STATUS_DRAFT, Proposal::STATUS_NEEDS_CHANGES], true)) {
            return redirect()
                ->route('proposals.show', $proposal)
                ->with('error', 'Solo puedes enviar propuestas en estado borrador.');
        }

        $oldStatus = $proposal->status;
        $proposal->update([
            'status' => Proposal::STATUS_SUBMITTED,
            'submitted_at' => now(),
            'updated_by' => $request->user()->id,
        ]);
        Review::create([
            'user_id' => $request->user()->id,
            'reviewable_type' => Proposal::class,
            'reviewable_id' => $proposal->id,
            'old_status' => $oldStatus,
            'new_status' => Proposal::STATUS_SUBMITTED,
        ]);
        app(AuditService::class)->record('proposal.submitted', $proposal, ['status' => $oldStatus], ['status' => Proposal::STATUS_SUBMITTED]);

        return redirect()
            ->route('proposals.show', $proposal)
            ->with('success', 'Propuesta enviada a revisión.');
    }

    protected function availableActivities(User $user): array
    {
        return Activity::query()
            ->when(! $user->can('activities.view_any'), function ($query) use ($user) {
                $query->where('artist_id', $user->artist?->id);
            })
            ->orderBy('title')
            ->pluck('title', 'id')
            ->toArray();
    }

    private function ensureActivityBelongsToArtist(?int $activityId, int $artistId): void
    {
        if ($activityId !== null) {
            abort_unless(Activity::whereKey($activityId)->where('artist_id', $artistId)->exists(), 422);
        }
    }
}
