<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReferralCommission extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'base_minor' => 'integer',
            'rate_bps' => 'integer',
            'amount_minor' => 'integer',
            'available_at' => 'datetime',
            'paid_at' => 'datetime',
            'metadata' => 'array',
        ];
    }

    public function attribution(): BelongsTo
    {
        return $this->belongsTo(ReferralAttribution::class, 'attribution_id');
    }

    public function payout(): BelongsTo
    {
        return $this->belongsTo(ReferralPayout::class, 'payout_id');
    }
}
