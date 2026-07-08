<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateArtistProfileRequest;
use App\Models\Artist;
use App\Models\Discipline;
use App\Models\Territory;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ArtistProfileController extends Controller
{
    public function edit(Request $request): View
    {
        $artist = $request->user()->artist;

        abort_if($artist === null, 404);

        $this->authorize('update', $artist);

        $artist->load(['profile', 'mainDiscipline', 'territory']);

        return view('artist-profile.edit', [
            'artist' => $artist,
            'disciplines' => Discipline::orderBy('name')->pluck('name', 'id'),
            'territories' => Territory::orderBy('name')->pluck('name', 'id'),
            'statuses' => Artist::$statuses,
        ]);
    }

    public function update(UpdateArtistProfileRequest $request): RedirectResponse
    {
        $artist = $request->user()->artist;

        abort_if($artist === null, 404);

        $this->authorize('update', $artist);

        $validated = $request->validated();

        $artistData = array_intersect_key($validated, array_flip([
            'legal_name',
            'public_name',
            'artistic_name',
            'email_contact',
            'phone',
            'website',
            'document_number',
            'region',
            'province',
            'commune',
            'address',
            'territory_id',
            'main_discipline_id',
            'bio_short',
            'bio_long',
        ]));

        $profileData = array_intersect_key($validated, array_flip([
            'experience',
            'education',
            'awards',
            'portfolio_url',
            'video_url',
            'availability',
            'representation',
            'press_links',
            'tech_rider',
            'stage_requirements',
        ]));

        $artistData['updated_by'] = $request->user()->id;

        $artist->update($artistData);

        $artist->profile()->updateOrCreate(
            ['artist_id' => $artist->id],
            $profileData
        );

        return redirect()
            ->route('profile.edit')
            ->with('success', 'Perfil actualizado correctamente.');
    }

    public function submit(Request $request): RedirectResponse
    {
        $artist = $request->user()->artist;

        abort_if($artist === null, 404);

        $this->authorize('submit', $artist);

        if (! $artist->isDraft()) {
            return redirect()
                ->route('profile.edit')
                ->with('error', 'Solo puedes enviar perfiles en estado borrador.');
        }

        $artist->update([
            'status' => Artist::STATUS_SUBMITTED,
            'submitted_at' => now(),
        ]);

        return redirect()
            ->route('profile.edit')
            ->with('success', 'Perfil enviado a revisión.');
    }
}
