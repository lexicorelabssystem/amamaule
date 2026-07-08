<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateArtistProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        $artist = $this->user()?->artist;

        return $artist !== null && $this->user()->can('update', $artist);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $artist = $this->user()?->artist;
        $artistId = $artist?->id;

        return [
            'legal_name' => ['required', 'string', 'max:255'],
            'public_name' => ['required', 'string', 'max:255'],
            'artistic_name' => ['nullable', 'string', 'max:255'],
            'email_contact' => ['required', 'email', 'max:255', "unique:artists,email_contact,{$artistId}"],
            'phone' => ['nullable', 'string', 'max:255'],
            'website' => ['nullable', 'url', 'max:255'],
            'document_number' => ['nullable', 'string', 'max:255'],
            'region' => ['nullable', 'string', 'max:255'],
            'province' => ['nullable', 'string', 'max:255'],
            'commune' => ['nullable', 'string', 'max:255'],
            'address' => ['nullable', 'string', 'max:255'],
            'territory_id' => ['nullable', 'exists:territories,id'],
            'main_discipline_id' => ['nullable', 'exists:disciplines,id'],
            'bio_short' => ['nullable', 'string', 'max:500'],
            'bio_long' => ['nullable', 'string'],
            'experience' => ['nullable', 'string'],
            'education' => ['nullable', 'string'],
            'awards' => ['nullable', 'string'],
            'portfolio_url' => ['nullable', 'url', 'max:255'],
            'video_url' => ['nullable', 'url', 'max:255'],
            'availability' => ['nullable', 'string', 'max:255'],
            'representation' => ['nullable', 'string'],
            'press_links' => ['nullable', 'array'],
            'press_links.*' => ['nullable', 'url', 'max:255'],
            'tech_rider' => ['nullable', 'string'],
            'stage_requirements' => ['nullable', 'string'],
        ];
    }

    public function messages(): array
    {
        return [
            'email_contact.unique' => 'Este correo de contacto ya está registrado para otro artista.',
        ];
    }
}
