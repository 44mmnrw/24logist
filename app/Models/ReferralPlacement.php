<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class ReferralPlacement extends Model
{
    protected $guarded = [];

    protected static function booted(): void
    {
        static::creating(function (self $placement): void {
            if (! $placement->code) {
                do {
                    $placement->code = Str::random(16);
                } while (static::query()->where('code', $placement->code)->exists());
            }
        });
        static::saving(function (self $placement): void {
            if ($placement->status === 'active' && blank($placement->erid)) {
                throw ValidationException::withMessages(['erid' => 'Для активации публичной ссылки необходим ERID.']);
            }
            if ($placement->status === 'active' && $placement->approved_at === null) {
                $placement->approved_at = now();
                $placement->approved_by_user_id = auth()->id();
            }
        });
    }

    protected function casts(): array
    {
        return [
            'placement_cost_minor' => 'integer',
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
            'approved_at' => 'datetime',
        ];
    }

    public function participant(): BelongsTo
    {
        return $this->belongsTo(ReferralParticipant::class, 'participant_id');
    }
}
