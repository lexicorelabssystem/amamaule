<?php

namespace App\Http\Controllers;

use App\Models\Activity;
use App\Models\Artist;
use App\Models\Discipline;
use App\Models\Territory;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;

class PublicCatalogController extends Controller
{
    public function artists(): JsonResponse
    {
        return response()->json(Cache::remember('public_catalog.artists', now()->addMinutes(10), function () {
            return Artist::query()
                ->with(['territory', 'mainDiscipline'])
                ->where('status', Artist::STATUS_APPROVED)
                ->orderBy('public_name')
                ->limit(100)
                ->get()
                ->map(fn (Artist $artist) => [
                    'id' => $artist->id,
                    'name' => $artist->displayName(),
                    'slug' => $artist->slug,
                    'commune' => $artist->territory?->name ?? $artist->commune,
                    'discipline' => $artist->mainDiscipline?->name,
                    'bio' => $artist->bio_short,
                ]);
        }));
    }

    public function activities(): JsonResponse
    {
        return response()->json(Cache::remember('public_catalog.activities', now()->addMinutes(10), function () {
            return Activity::query()
                ->with(['artist', 'territory'])
                ->where('status', Activity::STATUS_PUBLISHED)
                ->orderBy('start_date')
                ->limit(100)
                ->get()
                ->map(fn (Activity $activity) => [
                    'id' => $activity->id,
                    'title' => $activity->title,
                    'slug' => $activity->slug,
                    'artist' => $activity->artist?->displayName(),
                    'commune' => $activity->territory?->name,
                    'location' => $activity->location,
                    'start_date' => $activity->start_date?->toISOString(),
                    'is_free' => $activity->is_free,
                ]);
        }));
    }

    public function disciplines(): JsonResponse
    {
        return response()->json(Cache::remember('public_catalog.disciplines', now()->addMinutes(30), fn () => Discipline::query()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get(['id', 'name', 'slug', 'description'])));
    }

    public function territories(): JsonResponse
    {
        return response()->json(Cache::remember('public_catalog.territories', now()->addMinutes(30), fn () => Territory::query()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get(['id', 'name', 'region', 'province', 'slug'])));
    }
}
