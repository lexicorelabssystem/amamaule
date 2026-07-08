<?php

namespace App\Http\Controllers;

use App\Models\CommunityChannel;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CommunityChannelController extends Controller
{
    public function index(Request $request): View
    {
        abort_unless($request->user()->can('community.view'), 403);

        $channels = CommunityChannel::query()
            ->with('discipline')
            ->where('is_active', true)
            ->withCount('visibleMessages')
            ->orderBy('name')
            ->paginate(20);

        return view('community.index', compact('channels'));
    }

    public function show(Request $request, CommunityChannel $channel): View
    {
        abort_unless($request->user()->can('community.view') && $channel->is_active, 403);

        $channel->load('discipline');
        $messages = $channel->visibleMessages()
            ->with('user')
            ->oldest()
            ->paginate(30);

        return view('community.show', compact('channel', 'messages'));
    }
}
