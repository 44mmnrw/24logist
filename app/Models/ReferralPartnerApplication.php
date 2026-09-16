<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReferralPartnerApplication extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'terms_accepted' => 'boolean',
            'privacy_accepted' => 'boolean',
            'reviewed_at' => 'datetime',
        ];
    }

    public function participant(): BelongsTo
    {
        return $this->belongsTo(ReferralParticipant::class, 'participant_id');
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by_user_id');
    }
}
