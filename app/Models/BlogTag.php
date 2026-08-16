<?php

namespace App\Models;

use App\Support\LandingMedia;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class BlogTag extends Model
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
    ];

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function getUrl(): string
    {
        return route('blog.tag', $this->slug);
    }

    public function displayH1(): string
    {
        return filled($this->seo_h1)
            ? (string) $this->seo_h1
            : 'Статьи с тегом «'.$this->name.'»';
    }

    public function usageCount(): int
    {
        return BlogPost::query()->whereJsonContains('tags', $this->name)->count();
    }

    public function isUsed(): bool
    {
        return BlogPost::query()->whereJsonContains('tags', $this->name)->exists();
    }

    protected static function booted(): void
    {
        static::deleting(function (self $tag): void {
            if ($tag->isUsed()) {
                throw ValidationException::withMessages([
                    'tag' => 'Нельзя удалить тег, который используется в статьях.',
                ]);
            }
        });

        static::saving(function (self $tag): void {
            if (! filled($tag->slug)) {
                $tag->slug = $tag->name;
            }

            $baseSlug = Str::slug($tag->slug) ?: 'tag-'.substr(md5((string) $tag->name), 0, 10);
            $slug = $baseSlug;
            $suffix = 2;

            while (self::query()->where('slug', $slug)->whereKeyNot($tag->getKey())->exists()) {
                $slug = $baseSlug.'-'.$suffix++;
            }

            $tag->slug = $slug;
            $tag->og_image_path = LandingMedia::normalizePath($tag->og_image_path);
            $tag->twitter_image_path = LandingMedia::normalizePath($tag->twitter_image_path);
            $tag->schema_image_path = LandingMedia::normalizePath($tag->schema_image_path);
        });

        static::updated(function (self $tag): void {
            if (! $tag->wasChanged('name')) {
                return;
            }

            $oldName = trim((string) $tag->getOriginal('name'));

            BlogPost::query()
                ->whereJsonContains('tags', $oldName)
                ->get()
                ->each(function (BlogPost $post) use ($oldName, $tag): void {
                    $tags = array_map(
                        fn ($name): string => (string) $name === $oldName ? $tag->name : (string) $name,
                        (array) $post->tags,
                    );

                    $post->forceFill(['tags' => $tags])->saveQuietly();
                });
        });
    }
}
