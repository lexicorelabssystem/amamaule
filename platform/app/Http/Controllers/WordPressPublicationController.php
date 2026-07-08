<?php

namespace App\Http\Controllers;

use App\Models\Activity;
use App\Models\Artist;
use App\Services\WordPressPublicationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class WordPressPublicationController extends Controller
{
    public function publishArtist(Request $request, Artist $artist, WordPressPublicationService $service): RedirectResponse
    {
        abort_unless($request->user()->can('wordpress.publish') || $request->user()->can('wordpress.update'), 403);

        $publication = $service->publishArtist($artist);

        return back()->with($publication->status === 'failed' ? 'error' : 'status', $this->message($publication->status));
    }

    public function publishActivity(Request $request, Activity $activity, WordPressPublicationService $service): RedirectResponse
    {
        abort_unless($request->user()->can('wordpress.publish') || $request->user()->can('wordpress.update'), 403);

        $publication = $service->publishActivity($activity);

        return back()->with($publication->status === 'failed' ? 'error' : 'status', $this->message($publication->status));
    }

    public function unpublishArtist(Request $request, Artist $artist, WordPressPublicationService $service): RedirectResponse
    {
        abort_unless($request->user()->can('wordpress.unpublish'), 403);

        $publication = $service->unpublish($artist);

        return back()->with($publication->status === 'failed' ? 'error' : 'status', $this->message($publication->status));
    }

    public function unpublishActivity(Request $request, Activity $activity, WordPressPublicationService $service): RedirectResponse
    {
        abort_unless($request->user()->can('wordpress.unpublish'), 403);

        $publication = $service->unpublish($activity);

        return back()->with($publication->status === 'failed' ? 'error' : 'status', $this->message($publication->status));
    }

    private function message(string $status): string
    {
        return match ($status) {
            'published' => 'Contenido publicado o sincronizado con WordPress.',
            'draft' => 'Contenido despublicado en WordPress.',
            'failed' => 'La sincronizaci?n con WordPress fall?. Revisa el detalle registrado.',
            default => 'Estado WordPress actualizado.',
        };
    }
}
