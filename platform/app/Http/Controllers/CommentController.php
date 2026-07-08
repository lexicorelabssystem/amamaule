<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreCommentRequest;
use App\Models\Artist;
use App\Models\Comment;
use App\Models\Proposal;
use Illuminate\Http\RedirectResponse;

class CommentController extends Controller
{
    public function store(StoreCommentRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        $model = match ($validated['commentable_type']) {
            Artist::class => Artist::findOrFail($validated['commentable_id']),
            Proposal::class => Proposal::findOrFail($validated['commentable_id']),
        };

        $this->authorize('review', $model);

        Comment::create([
            'user_id' => $request->user()->id,
            'commentable_type' => $validated['commentable_type'],
            'commentable_id' => $validated['commentable_id'],
            'body' => $validated['body'],
            'is_internal' => true,
        ]);

        return redirect()->back()->with('success', 'Comentario guardado.');
    }
}
