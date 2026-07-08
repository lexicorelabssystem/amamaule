<?php

namespace App\Http\Controllers;

use App\Models\Activity;
use App\Models\Artist;
use App\Models\AuditLog;
use App\Models\Proposal;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(Request $request): View
    {
        $metrics = null;
        $audits = collect();
        if ($request->user()->can('audit.view')) {
            $metrics = [
                'artists' => Artist::count(), 'artists_pending' => Artist::pendingReview()->count(),
                'activities' => Activity::count(), 'proposals' => Proposal::count(),
                'proposals_pending' => Proposal::pendingReview()->count(),
            ];
            $audits = AuditLog::with('user')->latest()->limit(10)->get();
        }

        return view('dashboard', compact('metrics', 'audits'));
    }
}
