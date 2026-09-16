<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Validation\ValidationException;

class ReferralTerm extends Model
{
    protected $guarded = [];

    protected static function booted(): void
    {
        static::saving(function (self $terms): void {
            if ($terms->status !== 'active') {
                return;
            }
            if ($terms->starts_at === null || $terms->ends_at === null || $terms->published_at === null) {
                throw ValidationException::withMessages(['status' => 'Для активации заполните даты действия и публикации.']);
            }
            if ($terms->ends_at->lte($terms->starts_at)) {
                throw ValidationException::withMessages(['ends_at' => 'Дата окончания должна быть позже даты начала.']);
            }
        });
    }

    protected function casts(): array
    {
        return [
            'commission_bps' => 'integer',
            'commission_months' => 'integer',
            'invitee_discount_bps' => 'integer',
            'hold_days' => 'integer',
            'minimum_payout_minor' => 'integer',
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
            'published_at' => 'datetime',
        ];
    }

    public function participants(): HasMany
    {
        return $this->hasMany(ReferralParticipant::class, 'terms_id');
    }

    public function attributions(): HasMany
    {
        return $this->hasMany(ReferralAttribution::class, 'terms_id');
    }

    public function isActiveAt(?\DateTimeInterface $moment = null): bool
    {
        $moment ??= now();

        return $this->status === 'active'
            && ($this->starts_at === null || $this->starts_at->lte($moment))
            && ($this->ends_at === null || $this->ends_at->gte($moment));
    }
}
