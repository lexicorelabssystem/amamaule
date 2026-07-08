<?php

namespace App\Http\Requests;

use App\Models\Proposal;
use Illuminate\Foundation\Http\FormRequest;

class StoreCommentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('comments.create_internal') ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $table = $this->input('commentable_type') === Proposal::class
            ? 'proposals'
            : 'artists';

        return [
            'commentable_type' => ['required', 'string', 'in:App\\Models\\Artist,App\\Models\\Proposal'],
            'commentable_id' => ['required', 'integer', 'exists:'.$table.',id'],
            'body' => ['required', 'string', 'max:2000'],
        ];
    }
}
