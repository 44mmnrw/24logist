<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CommunityNotificationDelivery extends Model
{
    protected $fillable = [
        'community_notification_id', 'community_identity_id', 'provider', 'status',
        'attempts', 'last_error', 'sent_at',
    ];

    protected function casts(): array
    {
        return ['sent_at' => 'datetime'];
    }

    public function notification(): BelongsTo
    {
        return $this->belongsTo(CommunityNotification::class, 'community_notification_id');
    }

    public function identity(): BelongsTo
    {
        return $this->belongsTo(CommunityIdentity::class, 'community_identity_id');
    }
}
