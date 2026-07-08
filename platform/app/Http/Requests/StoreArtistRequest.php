<?php

namespace App\Http\Requests;

use App\Models\Artist;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreArtistRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('artists.create');
    }

    public function rules(): array
    {
        return [
            'legal_name' => ['nullable', 'string', 'max:255'],
            'public_name' => ['nullable', 'string', 'max:255'],
            'artistic_name' => ['nullable', 'string', 'max:255'],
            'email_contact' => ['nullable', 'email', 'max:255', 'unique:artists,email_contact'],
            'phone' => ['nullable', 'string', 'max:50'],
            'website' => ['nullable', 'url', 'max:255'],
            'document_number' => ['nullable', 'string', 'max:50', 'unique:artists,document_number'],
            'region' => ['nullable', 'string', 'max:100'],
            'province' => ['nullable', 'string', 'max:100'],
            'commune' => ['nullable', 'string', 'max:100'],
            'address' => ['nullable', 'string', 'max:255'],
            'territory_id' => ['nullable', 'integer', 'exists:territories,id'],
            'main_discipline_id' => ['nullable', 'integer', 'exists:disciplines,id'],
            'disciplines' => ['nullable', 'array'],
            'disciplines.*' => ['integer', 'exists:disciplines,id'],
            'bio_short' => ['nullable', 'string', 'max:500'],
            'bio_long' => ['nullable', 'string'],
            'social_networks' => ['nullable', 'array'],
            'status' => ['nullable', 'string', Rule::in(Artist::$statuses)],
        ];
    }

    public function attributes(): array
    {
        return [
            'public_name' => 'nombre artístico',
            'legal_name' => 'nombre legal',
            'email_contact' => 'email de contacto',
            'territory_id' => 'comuna',
            'main_discipline_id' => 'disciplina principal',
        ];
    }
}
