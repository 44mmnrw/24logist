<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class SeoKeywordCluster extends Model
{
    protected $fillable = ['name', 'slug', 'seed_phrase', 'description', 'target_url', 'search_intent', 'is_active', 'sort_order'];

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    public function keywords(): HasMany
    {
        return $this->hasMany(SeoKeyword::class);
    }

    protected static function booted(): void
    {
        static::saving(function (self $cluster): void {
            $cluster->slug = Str::slug($cluster->slug ?: $cluster->name);
        });
    }
}
