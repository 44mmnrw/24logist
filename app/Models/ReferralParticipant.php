<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class ReferralParticipant extends Model
{
    protected $guarded = [];

    protected static function booted(): void
    {
        static::creating(function (self $participant): void {
            if (! $participant->code) {
                $participant->code = static::uniqueToken('code', 12);
            }
        });
    }

    protected function casts(): array
    {
        return [
            'commission_bps_override' => 'integer',
            'commission_months_override' => 'integer',
            'invitee_discount_bps_override' => 'integer',
            'hold_days_override' => 'integer',
            'minimum_payout_minor_override' => 'integer',
            'offer_accepted_at' => 'datetime',
            'bank_details_verified_at' => 'datetime',
            'bank_details' => 'encrypted:array',
            'suspended_at' => 'datetime',
        ];
    }

    public function terms(): BelongsTo
    {
        return $this->belongsTo(ReferralTerm::class, 'terms_id');
    }

    public function placements(): HasMany
    {
        return $this->hasMany(ReferralPlacement::class, 'participant_id');
    }

    public function attributions(): HasMany
    {
        return $this->hasMany(ReferralAttribution::class, 'participant_id');
    }

    public function payouts(): HasMany
    {
        return $this->hasMany(ReferralPayout::class, 'participant_id');
    }

    public function isEligible(): bool
    {
        $settings = ReferralProgramSetting::current();

        return $settings->is_enabled
            && $this->status === 'active'
            && $this->suspended_at === null
            && $this->offer_accepted_at !== null
            && $this->bank_details_verified_at !== null;
    }

    /** @return array{commission_bps:int,commission_months:int,invitee_discount_bps:int,hold_days:int,minimum_payout_minor:int} */
    public function effectiveTerms(ReferralTerm $terms): array
    {
        return [
            'commission_bps' => $this->commission_bps_override ?? $terms->commission_bps,
            'commission_months' => $this->commission_months_override ?? $terms->commission_months,
            'invitee_discount_bps' => $this->invitee_discount_bps_override ?? $terms->invitee_discount_bps,
            'hold_days' => $this->hold_days_override ?? $terms->hold_days,
            'minimum_payout_minor' => $this->minimum_payout_minor_override ?? $terms->minimum_payout_minor,
        ];
    }

    private static function uniqueToken(string $column, int $length): string
    {
        do {
            $token = Str::random($length);
        } while (static::query()->where($column, $token)->exists());

        return $token;
    }
}
