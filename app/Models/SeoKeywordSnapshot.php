<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SeoKeywordSnapshot extends Model
{
    protected $fillable = [
        'seo_keyword_id', 'seo_research_run_id', 'source', 'wordstat_count', 'position',
        'result_url', 'recorded_at', 'raw',
    ];

    protected function casts(): array
    {
        return ['recorded_at' => 'datetime', 'raw' => 'array'];
    }

    public function keyword(): BelongsTo
    {
        return $this->belongsTo(SeoKeyword::class, 'seo_keyword_id');
    }

    public function run(): BelongsTo
    {
        return $this->belongsTo(SeoResearchRun::class, 'seo_research_run_id');
    }
}
