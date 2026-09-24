<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EtrnRoulettePlayer extends Model
{
    protected $fillable = ['community_user_id', 'attempts', 'joined_at', 'last_played_at'];

    protected function casts(): array
    {
        return [
            'joined_at' => 'datetime',
            'last_played_at' => 'datetime',
        ];
    }

    public function communityUser(): BelongsTo
    {
        return $this->belongsTo(CommunityUser::class);
    }
}
