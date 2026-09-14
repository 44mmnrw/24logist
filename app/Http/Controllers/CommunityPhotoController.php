<?php

namespace App\Http\Controllers;

use App\Models\CommunityPhoto;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class CommunityPhotoController extends Controller
{
    public function show(CommunityPhoto $photo): StreamedResponse
    {
        $comment = $photo->comment;
        $post = $comment?->post ?: $photo->post;
        $published = $post?->status === 'published' && ($comment === null || $comment->status === 'published');
        $moderator = auth('community')->user()?->isModerator()
            && in_array($post?->status, ['published', 'hidden'], true)
            && ($comment === null || in_array($comment->status, ['published', 'hidden'], true));
        abort_unless($published || $moderator, 404);
        abort_unless(Storage::disk('local')->exists($photo->path), 404);

        return Storage::disk('local')->response($photo->path, null, [
            'Content-Type' => 'image/webp',
            'Cache-Control' => 'private, max-age=300',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }
}
