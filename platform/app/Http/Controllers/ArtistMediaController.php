<?php

namespace App\Http\Controllers;

use App\Models\Artist;
use App\Services\MediaUploadService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ArtistMediaController extends Controller
{
    public function __construct(
        protected MediaUploadService $mediaUpload
    ) {}

    public function avatar(Request $request, Artist $artist): RedirectResponse
    {
        $this->authorize('update', $artist);

        $request->validate([
            'avatar' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
        ]);

        $old = $artist->avatar;
        $media = $this->mediaUpload->queue($request->file('avatar'), $artist, 'avatar');
        $artist->update(['avatar_media_id' => $media->id]);
        $old?->delete();

        return redirect()->back()->with('status', 'Foto de perfil en proceso. Se actualizará en unos segundos.');
    }

    public function cover(Request $request, Artist $artist): RedirectResponse
    {
        $this->authorize('update', $artist);

        $request->validate([
            'cover' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
        ]);

        $old = $artist->cover;
        $media = $this->mediaUpload->queue($request->file('cover'), $artist, 'cover');
        $artist->update(['cover_media_id' => $media->id]);
        $old?->delete();

        return redirect()->back()->with('status', 'Portada en proceso. Se actualizará en unos segundos.');
    }
}
