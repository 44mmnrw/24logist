<?php

namespace App\Services\Community;

use App\Models\CommunityLoginChallenge;
use Illuminate\Support\Facades\URL;

final class MaxLoginReturnService
{
    public function url(CommunityLoginChallenge $challenge): string
    {
        return URL::temporarySignedRoute(
            'community.auth.max.complete',
            now()->addSeconds((int) config('community.max.return_link_ttl', 600)),
            ['challenge' => $challenge->getKey()],
        );
    }
}
