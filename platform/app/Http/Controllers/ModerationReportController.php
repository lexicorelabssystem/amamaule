<?php

namespace App\Http\Controllers;

use App\Models\CommunityMessage;
use App\Models\ModerationReport;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ModerationReportController extends Controller
{
    public function index(Request $request): View
    {
        abort_unless($request->user()->can('community.moderate'), 403);

        $reports = ModerationReport::query()
            ->with(['reporter', 'reportable.user', 'reportable.channel'])
            ->latest()
            ->paginate(20);

        return view('moderation-reports.index', compact('reports'));
    }

    public function store(Request $request, CommunityMessage $message): RedirectResponse
    {
        abort_unless($request->user()->can('community.view'), 403);
        abort_unless($message->status === CommunityMessage::STATUS_VISIBLE, 404);

        $data = $request->validate([
            'reason' => ['required', 'string', 'max:120'],
            'details' => ['nullable', 'string', 'max:1000'],
        ]);

        $message->reports()->create([
            'reporter_id' => $request->user()->id,
            'reason' => $data['reason'],
            'details' => $data['details'] ?? null,
        ]);

        return back()->with('status', 'Reporte enviado a moderaci?n.');
    }

    public function resolve(Request $request, ModerationReport $report): RedirectResponse
    {
        abort_unless($request->user()->can('community.moderate'), 403);

        $data = $request->validate([
            'hide_content' => ['nullable', 'boolean'],
        ]);

        $report->load('reportable');
        $report->resolve($request->user(), (bool) ($data['hide_content'] ?? false));

        return redirect()
            ->route('moderation-reports.index')
            ->with('status', 'Reporte resuelto.');
    }
}
