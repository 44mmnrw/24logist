<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class BlogCategory extends Model
{
    protected $fillable = [
        'name',
        'slug',
        'description',
        'is_active',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    /**
     * @return HasMany<BlogPost>
     */
    public function posts(): HasMany
    {
        return $this->hasMany(BlogPost::class);
    }

    protected static function booted(): void
    {
        static::saving(function (self $category): void {
            if (! filled($category->slug)) {
                $category->slug = $category->name;
            }

            $category->slug = Str::slug($category->slug);
        });
    }
}
