<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CommunityLoginChallenge extends Model
{
    protected $fillable = [
        'token_hash', 'browser_session_hash', 'link_to_user_id', 'community_user_id', 'status',
        'expires_at', 'approved_at', 'consumed_at', 'prompt_sent_at', 'return_sent_at', 'return_consumed_at',
    ];

    protected function casts(): array
    {
        return [
            'expires_at' => 'datetime',
            'approved_at' => 'datetime',
            'consumed_at' => 'datetime',
            'prompt_sent_at' => 'datetime',
            'return_sent_at' => 'datetime',
            'return_consumed_at' => 'datetime',
        ];
    }

    public function communityUser(): BelongsTo
    {
        return $this->belongsTo(CommunityUser::class);
    }
}
