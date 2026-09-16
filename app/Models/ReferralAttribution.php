<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ReferralAttribution extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'commission_bps' => 'integer',
            'commission_months' => 'integer',
            'invitee_discount_bps' => 'integer',
            'hold_days' => 'integer',
            'minimum_payout_minor' => 'integer',
            'attributed_at' => 'datetime',
            'confirmed_at' => 'datetime',
            'first_paid_at' => 'datetime',
            'commission_ends_at' => 'datetime',
            'discount_used_at' => 'datetime',
        ];
    }

    public function participant(): BelongsTo
    {
        return $this->belongsTo(ReferralParticipant::class, 'participant_id');
    }

    public function placement(): BelongsTo
    {
        return $this->belongsTo(ReferralPlacement::class, 'placement_id');
    }

    public function terms(): BelongsTo
    {
        return $this->belongsTo(ReferralTerm::class, 'terms_id');
    }

    public function commissions(): HasMany
    {
        return $this->hasMany(ReferralCommission::class, 'attribution_id');
    }
}
