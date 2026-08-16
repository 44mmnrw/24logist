<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SeoResearchRun extends Model
{
    protected $fillable = [
        'type', 'source', 'status', 'region_id', 'device', 'total_items', 'processed_items',
        'started_at', 'finished_at', 'metadata', 'error',
    ];

    protected function casts(): array
    {
        return ['started_at' => 'datetime', 'finished_at' => 'datetime', 'metadata' => 'array'];
    }

    public function snapshots(): HasMany
    {
        return $this->hasMany(SeoKeywordSnapshot::class);
    }
}
