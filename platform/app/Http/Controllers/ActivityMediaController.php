<?php

namespace App\Http\Controllers;

use App\Models\Activity;
use App\Models\Media;
use App\Services\MediaUploadService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ActivityMediaController extends Controller
{
    public function __construct(
        protected MediaUploadService $mediaUpload
    ) {}

    public function store(Request $request, Activity $activity): RedirectResponse
    {
        $this->authorize('update', $activity);

        $request->validate([
            'images' => ['required', 'array', 'max:10'],
            'images.*' => ['required', 'image', 'mimes:jpg,jpeg,png,webp,gif', 'max:5120'],
        ]);

        foreach ($request->file('images') as $file) {
            $this->mediaUpload->upload($file, $activity, 'gallery');
        }

        return redirect()
            ->route('activities.edit', $activity)
            ->with('success', 'Imágenes subidas correctamente.');
    }

    public function setCover(Activity $activity, Media $media): RedirectResponse
    {
        $this->authorize('update', $activity);

        abort_if($media->mediable_id !== $activity->id || $media->mediable_type !== Activity::class, 403);

        $activity->media()->update(['is_cover' => false]);
        $media->update(['is_cover' => true]);
        $activity->update(['cover_media_id' => $media->id]);

        return redirect()
            ->route('activities.edit', $activity)
            ->with('success', 'Portada actualizada.');
    }

    public function destroy(Activity $activity, Media $media): RedirectResponse
    {
        $this->authorize('update', $activity);

        abort_if($media->mediable_id !== $activity->id || $media->mediable_type !== Activity::class, 403);

        if ($activity->cover_media_id === $media->id) {
            $activity->update(['cover_media_id' => null]);
        }

        $media->delete();

        return redirect()
            ->route('activities.edit', $activity)
            ->with('success', 'Imagen eliminada.');
    }

    public function reorder(Request $request, Activity $activity): RedirectResponse
    {
        $this->authorize('update', $activity);

        $request->validate([
            'order' => ['required', 'array'],
            'order.*' => ['integer', 'exists:media,id'],
        ]);

        foreach ($request->input('order') as $index => $mediaId) {
            $activity->media()->where('id', $mediaId)->update(['order' => $index + 1]);
        }

        return redirect()
            ->route('activities.edit', $activity)
            ->with('success', 'Orden actualizado.');
    }
}
