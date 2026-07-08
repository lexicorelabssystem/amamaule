<?php

namespace App\Http\Controllers;

use App\Models\Artist;
use App\Services\AuditService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ProfileReviewController extends Controller
{
    public function index(): View
    {
        $this->authorize('viewAny', Artist::class);

        $artists = Artist::with(['user', 'mainDiscipline', 'territory'])
            ->pendingReview()
            ->latest('submitted_at')
            ->paginate(15);

        return view('profile-reviews.index', compact('artists'));
    }

    public function show(Artist $artist): View
    {
        $this->authorize('review', $artist);

        $artist->load(['user', 'profile', 'mainDiscipline', 'territory', 'comments.user']);

        return view('profile-reviews.show', compact('artist'));
    }

    public function approve(Request $request, Artist $artist): RedirectResponse
    {
        $this->authorize('approve', $artist);
        $oldStatus = $artist->status;
        $artist->update([
            'status' => Artist::STATUS_APPROVED,
            'approved_at' => now(),
            'approved_by' => $request->user()->id,
        ]);
        app(AuditService::class)->record('artist.approved', $artist, ['status' => $oldStatus], ['status' => Artist::STATUS_APPROVED]);

        return redirect()
            ->route('profile-reviews.index')
            ->with('success', 'Perfil aprobado.');
    }

    public function reject(Request $request, Artist $artist): RedirectResponse
    {
        $this->authorize('reject', $artist);

        $oldStatus = $artist->status;
        $artist->update([
            'status' => Artist::STATUS_REJECTED,
        ]);
        app(AuditService::class)->record('artist.rejected', $artist, ['status' => $oldStatus], ['status' => Artist::STATUS_REJECTED]);

        return redirect()
            ->route('profile-reviews.index')
            ->with('success', 'Perfil rechazado.');
    }

    public function requestChanges(Request $request, Artist $artist): RedirectResponse
    {
        $this->authorize('requestChanges', $artist);

        $oldStatus = $artist->status;
        $artist->update([
            'status' => Artist::STATUS_NEEDS_CHANGES,
        ]);
        app(AuditService::class)->record('artist.changes_requested', $artist, ['status' => $oldStatus], ['status' => Artist::STATUS_NEEDS_CHANGES]);

        return redirect()
            ->route('profile-reviews.index')
            ->with('success', 'Se solicitaron cambios al perfil.');
    }
}
