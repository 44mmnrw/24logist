<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SeoKeyword extends Model
{
    protected $fillable = [
        'seo_keyword_cluster_id', 'phrase', 'normalized_phrase', 'identity_hash', 'region_id', 'device', 'source_type',
        'target_url', 'is_active', 'latest_wordstat_count', 'wordstat_updated_at', 'latest_position',
        'latest_result_url', 'position_checked_at',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'wordstat_updated_at' => 'datetime',
            'position_checked_at' => 'datetime',
        ];
    }

    public function cluster(): BelongsTo
    {
        return $this->belongsTo(SeoKeywordCluster::class, 'seo_keyword_cluster_id');
    }

    public function snapshots(): HasMany
    {
        return $this->hasMany(SeoKeywordSnapshot::class);
    }

    public static function normalizePhrase(string $phrase): string
    {
        return mb_strtolower(trim((string) preg_replace('/\s+/u', ' ', $phrase)));
    }

    public static function identityHash(string $phrase, string $regionId, string $device): string
    {
        return hash('sha256', self::normalizePhrase($phrase).'|'.$regionId.'|'.strtoupper($device));
    }

    protected static function booted(): void
    {
        static::saving(function (self $keyword): void {
            $keyword->normalized_phrase = self::normalizePhrase($keyword->phrase);
            $keyword->region_id = trim((string) ($keyword->region_id ?: '225'));
            $keyword->device = strtoupper((string) ($keyword->device ?: 'DEVICE_ALL'));
            $keyword->identity_hash = self::identityHash($keyword->phrase, $keyword->region_id, $keyword->device);
        });
    }
}
