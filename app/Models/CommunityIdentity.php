<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CommunityIdentity extends Model
{
    protected $fillable = [
        'community_user_id', 'provider', 'provider_user_id', 'bot_access',
        'notifications_enabled', 'bot_status', 'last_verified_at',
    ];

    protected function casts(): array
    {
        return [
            'bot_access' => 'boolean',
            'notifications_enabled' => 'boolean',
            'last_verified_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(CommunityUser::class, 'community_user_id');
    }

    public function deliveries(): HasMany
    {
        return $this->hasMany(CommunityNotificationDelivery::class);
    }
}
