<?php

namespace App\Http\Requests;

use App\Models\Proposal;
use Illuminate\Foundation\Http\FormRequest;

class StoreProposalRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', Proposal::class) ?? false;
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
