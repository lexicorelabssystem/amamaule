<?php

namespace App\Http\Controllers;

use App\Models\Proposal;
use App\Models\Review;
use App\Notifications\ProposalStatusChanged;
use App\Services\AuditService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class ProposalReviewController extends Controller
{
    public function index(Request $request): View
    {
        abort_unless($request->user()->can('proposals.review'), 403);
        $proposals = Proposal::with(['artist', 'activity'])->pendingReview()
            ->when($request->filled('status'), fn ($query) => $query->where('status', $request->string('status')))
            ->when($request->filled('search'), fn ($query) => $query->where('title', 'like', '%'.$request->string('search').'%'))
            ->oldest('submitted_at')->paginate(15)->withQueryString();

        return view('proposal-reviews.index', compact('proposals'));
    }

    public function start(Request $request, Proposal $proposal): RedirectResponse
    {
        $this->authorize('review', $proposal);
        abort_unless($proposal->status === Proposal::STATUS_SUBMITTED, 422);

        return $this->transition($request, $proposal, Proposal::STATUS_IN_REVIEW);
    }

    public function approve(Request $request, Proposal $proposal): RedirectResponse
    {
        $this->authorize('approve', $proposal);
        $this->validateDecision($request, false);

        return $this->transition($request, $proposal, Proposal::STATUS_APPROVED);
    }

    public function reject(Request $request, Proposal $proposal): RedirectResponse
    {
        $this->authorize('reject', $proposal);
        $this->validateDecision($request, true);

        return $this->transition($request, $proposal, Proposal::STATUS_REJECTED);
    }

    public function requestChanges(Request $request, Proposal $proposal): RedirectResponse
    {
        $this->authorize('requestChanges', $proposal);
        $this->validateDecision($request, true);

        return $this->transition($request, $proposal, Proposal::STATUS_NEEDS_CHANGES);
    }

    private function validateDecision(Request $request, bool $required): void
    {
        $request->validate([
            'comment' => [$required ? 'required' : 'nullable', 'string', 'max:2000'],
            'score' => ['nullable', 'integer', 'between:1,100'],
        ]);
    }

    private function transition(Request $request, Proposal $proposal, string $newStatus): RedirectResponse
    {
        abort_unless(in_array($proposal->status, [Proposal::STATUS_SUBMITTED, Proposal::STATUS_IN_REVIEW], true), 422);
        $oldStatus = $proposal->status;
        DB::transaction(function () use ($request, $proposal, $newStatus) {
            Review::create([
                'user_id' => $request->user()->id, 'reviewable_type' => Proposal::class,
                'reviewable_id' => $proposal->id, 'old_status' => $proposal->status,
                'new_status' => $newStatus, 'comment' => $request->input('comment'),
                'score' => $request->integer('score') ?: null,
            ]);
            $proposal->update([
                'status' => $newStatus, 'score' => $request->integer('score') ?: $proposal->score,
                'approved_at' => $newStatus === Proposal::STATUS_APPROVED ? now() : null,
                'approved_by' => $newStatus === Proposal::STATUS_APPROVED ? $request->user()->id : null,
                'updated_by' => $request->user()->id,
            ]);
        });
        $proposal->refresh();
        app(AuditService::class)->record('proposal.status_changed', $proposal, ['status' => $oldStatus], ['status' => $newStatus, 'score' => $proposal->score]);
        $proposal->artist->user?->notify(new ProposalStatusChanged($proposal, $oldStatus, $request->input('comment')));

        return redirect()->route('proposals.show', $proposal)->with('success', 'Estado de la propuesta actualizado.');
    }
}
