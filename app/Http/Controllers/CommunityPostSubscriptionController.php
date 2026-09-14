<?php

namespace App\Http\Controllers;

use App\Models\CommunityPost;
use App\Models\CommunityPostSubscription;
use Illuminate\Http\RedirectResponse;

class CommunityPostSubscriptionController extends Controller
{
    public function store(CommunityPost $post): RedirectResponse
    {
        abort_unless($post->status === 'published', 404);
        CommunityPostSubscription::query()->firstOrCreate([
            'community_post_id' => $post->id,
            'community_user_id' => auth('community')->id(),
        ]);

        return redirect($post->getUrl())->with('status', 'Вы подписались на ответы в теме.');
    }

    public function destroy(CommunityPost $post): RedirectResponse
    {
        CommunityPostSubscription::query()
            ->where('community_post_id', $post->id)
            ->where('community_user_id', auth('community')->id())
            ->delete();

        return redirect($post->getUrl())->with('status', 'Подписка на тему отключена.');
    }
}
