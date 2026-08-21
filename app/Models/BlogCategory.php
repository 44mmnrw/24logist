<?php

namespace App\Models;

use App\Services\SitemapService;
use App\Support\LandingMedia;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class BlogCategory extends Model
{
    protected $fillable = [
        'name',
        'slug',
        'description',
        'seo_h1',
        'meta_title',
        'meta_description',
        'meta_keywords',
        'meta_robots',
        'canonical_url',
        'og_title',
        'og_description',
        'og_image_path',
        'og_type',
        'twitter_title',
        'twitter_description',
        'twitter_image_path',
        'twitter_card',
        'schema_type',
        'schema_headline',
        'schema_description',
        'schema_image_path',
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

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function getUrl(): string
    {
        return route('blog.category', $this->slug);
    }

    public function displayH1(): string
    {
        return filled($this->seo_h1)
            ? (string) $this->seo_h1
            : 'Статьи рубрики «'.$this->name.'»';
    }

    protected static function booted(): void
    {
        static::saving(function (self $category): void {
            if (! filled($category->slug)) {
                $category->slug = $category->name;
            }

            $category->slug = Str::slug($category->slug);
            $category->og_image_path = LandingMedia::normalizePath($category->og_image_path);
            $category->twitter_image_path = LandingMedia::normalizePath($category->twitter_image_path);
            $category->schema_image_path = LandingMedia::normalizePath($category->schema_image_path);
        });

        static::saved(function (): void {
            app(SitemapService::class)->clearCache();
        });
        static::deleted(function (): void {
            app(SitemapService::class)->clearCache();
        });
    }
}
