<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EtrnRoulettePlayer extends Model
{
    protected $fillable = [
        'community_user_id', 'attempts', 'joined_at', 'last_played_at',
        'contact_email', 'contact_verified_at', 'contact_consent_at', 'contact_consent_ip',
    ];

    protected function casts(): array
    {
        return [
            'joined_at' => 'datetime',
            'last_played_at' => 'datetime',
            'contact_verified_at' => 'datetime',
            'contact_consent_at' => 'datetime',
        ];
    }

    public function communityUser(): BelongsTo
    {
        return $this->belongsTo(CommunityUser::class);
    }
}
