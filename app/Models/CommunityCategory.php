<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CommunityCategory extends Model
{
    protected $fillable = ['name', 'slug', 'description', 'sort_order', 'is_active', 'posting_enabled'];

    protected function casts(): array
    {
        return ['is_active' => 'boolean', 'posting_enabled' => 'boolean'];
    }

    public function posts(): HasMany
    {
        return $this->hasMany(CommunityPost::class);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }
}
