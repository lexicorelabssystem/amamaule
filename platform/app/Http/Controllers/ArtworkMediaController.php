<?php

namespace App\Http\Controllers;

use App\Models\Artwork;
use App\Models\Media;
use App\Services\MediaUploadService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ArtworkMediaController extends Controller
{
    public function __construct(
        protected MediaUploadService $mediaUpload
    ) {}

    public function store(Request $request, Artwork $artwork): RedirectResponse
    {
        $this->authorize('update', $artwork);

        $request->validate([
            'images' => ['required', 'array', 'max:10'],
            'images.*' => ['required', 'image', 'mimes:jpg,jpeg,png,webp,gif', 'max:5120'],
        ]);

        foreach ($request->file('images') as $file) {
            $this->mediaUpload->queue($file, $artwork, 'gallery');
        }

        return redirect()
            ->route('artworks.edit', $artwork)
            ->with('success', 'Imágenes en proceso. Se mostrarán en unos segundos.');
    }

    public function setCover(Artwork $artwork, Media $media): RedirectResponse
    {
        $this->authorize('update', $artwork);

        abort_if($media->mediable_id !== $artwork->id || $media->mediable_type !== Artwork::class, 403);

        $artwork->media()->update(['is_cover' => false]);
        $media->update(['is_cover' => true]);
        $artwork->update(['cover_media_id' => $media->id]);

        return redirect()
            ->route('artworks.edit', $artwork)
            ->with('success', 'Portada actualizada.');
    }

    public function destroy(Artwork $artwork, Media $media): RedirectResponse
    {
        $this->authorize('update', $artwork);

        abort_if($media->mediable_id !== $artwork->id || $media->mediable_type !== Artwork::class, 403);

        if ($artwork->cover_media_id === $media->id) {
            $artwork->update(['cover_media_id' => null]);
        }

        $media->delete();

        return redirect()
            ->route('artworks.edit', $artwork)
            ->with('success', 'Imagen eliminada.');
    }
}
