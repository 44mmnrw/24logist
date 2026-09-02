<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CommunityLoginChallenge extends Model
{
    protected $fillable = [
        'token_hash', 'browser_session_hash', 'link_to_user_id', 'community_user_id', 'status',
        'expires_at', 'approved_at', 'consumed_at',
    ];

    protected function casts(): array
    {
        return [
            'expires_at' => 'datetime',
            'approved_at' => 'datetime',
            'consumed_at' => 'datetime',
        ];
    }
}
