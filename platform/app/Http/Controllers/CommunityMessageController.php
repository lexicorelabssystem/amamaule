<?php

namespace App\Http\Controllers;

use App\Models\CommunityChannel;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class CommunityMessageController extends Controller
{
    public function store(Request $request, CommunityChannel $channel): RedirectResponse
    {
        abort_unless($request->user()->can('community.message') && $channel->is_active, 403);

        $data = $request->validate([
            'body' => ['required', 'string', 'min:2', 'max:2000'],
        ]);

        $channel->messages()->create([
            'user_id' => $request->user()->id,
            'body' => $data['body'],
        ]);

        return redirect()
            ->route('community.channels.show', $channel)
            ->with('status', 'Mensaje publicado.');
    }
}
