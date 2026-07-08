<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateProposalRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('update', $this->route('proposal')) ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'activity_id' => ['nullable', 'exists:activities,id'],
            'title' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string'],
            'objectives' => ['nullable', 'string'],
            'target_audience' => ['nullable', 'string'],
            'requirements' => ['nullable', 'string'],
            'budget' => ['nullable', 'numeric', 'min:0'],
        ];
    }
}
