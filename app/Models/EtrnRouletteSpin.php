<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EtrnRouletteSpin extends Model
{
    protected $fillable = [
        'request_id', 'actor_key', 'community_user_id', 'etrn_roulette_player_id', 'is_jackpot', 'outcome',
    ];

    protected function casts(): array
    {
        return ['is_jackpot' => 'boolean', 'outcome' => 'array'];
    }

    public function communityUser(): BelongsTo
    {
        return $this->belongsTo(CommunityUser::class)->withTrashed();
    }

    public function player(): BelongsTo
    {
        return $this->belongsTo(EtrnRoulettePlayer::class, 'etrn_roulette_player_id');
    }
}
