<?php

namespace App\Services\Community;

use App\Models\CommunityComment;
use App\Models\CommunityPost;

class CommunityCommentCounter
{
    public function sync(CommunityPost $post): void
    {
        $post->update([
            'comments_count' => CommunityComment::query()
                ->where('community_post_id', $post->id)
                ->where('status', 'published')
                ->count(),
        ]);
    }
}
