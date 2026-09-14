<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CommunityPostSubscription extends Model
{
    protected $fillable = ['community_post_id', 'community_user_id'];

    public function subscriber(): BelongsTo
    {
        return $this->belongsTo(CommunityUser::class, 'community_user_id');
    }
}
