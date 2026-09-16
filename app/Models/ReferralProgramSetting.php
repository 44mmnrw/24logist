<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ReferralProgramSetting extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'is_enabled' => 'boolean',
            'public_placements_enabled' => 'boolean',
            'attribution_days' => 'integer',
        ];
    }

    public static function current(): self
    {
        return static::query()->firstOrCreate(['id' => 1], [
            'is_enabled' => false,
            'public_placements_enabled' => false,
            'attribution_days' => 90,
        ]);
    }
}
