<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CommunityUserSession extends Model
{
    protected $fillable = [
        'community_user_id', 'community_identity_id', 'session_id_hash', 'provider',
        'ip_address', 'user_agent', 'logged_in_at', 'last_seen_at', 'expires_at', 'logged_out_at',
    ];

    protected function casts(): array
    {
        return [
            'logged_in_at' => 'datetime',
            'last_seen_at' => 'datetime',
            'expires_at' => 'datetime',
            'logged_out_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(CommunityUser::class, 'community_user_id');
    }

    public function identity(): BelongsTo
    {
        return $this->belongsTo(CommunityIdentity::class, 'community_identity_id');
    }

    public function isActive(): bool
    {
        return $this->logged_out_at === null
            && ($this->expires_at === null || $this->expires_at->isFuture());
    }
}
