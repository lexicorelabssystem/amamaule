<?php

namespace App\Http\Controllers;

use App\Models\Media;
use Illuminate\Http\JsonResponse;

class MediaStatusController extends Controller
{
    public function show(Media $media): JsonResponse
    {
        $this->authorize('update', $media->mediable);

        return response()->json([
            'id' => $media->id,
            'status' => $media->status,
            'error_message' => $media->error_message,
            'url' => $media->fullUrl(),
            'thumbnail_url' => $media->thumbnailUrl(),
        ]);
    }
}
