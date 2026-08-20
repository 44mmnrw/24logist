<?php

namespace App\Models;

use Filament\Forms\Components\RichEditor\RichContentRenderer;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class CmsPage extends Model
{
    protected $fillable = [
        'slug',
        'title',
        'body',
        'extra',
        'meta_title',
        'meta_description',
        'is_published',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'is_published' => 'boolean',
            'extra' => 'array',
        ];
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function getUrl(): string
    {
        return route('pages.show', $this->slug);
    }

    public static function normalizeSlug(?string $value): string
    {
        $value = trim((string) ($value ?? ''));

        if ($value === '') {
            return '';
        }

        $path = parse_url($value, PHP_URL_PATH);
        $value = $path !== false && $path !== null ? $path : $value;
        $segments = array_values(array_filter(explode('/', trim($value, '/')), fn (string $segment): bool => $segment !== ''));

        while (($segments[0] ?? null) === 'pages') {
            array_shift($segments);
        }

        return Str::slug(implode('-', $segments));
    }

    public function renderBody(): string
    {
        return RichContentRenderer::make($this->body ?? '')->toHtml();
    }

    public function displayTitle(): string
    {
        return $this->meta_title ?: $this->title;
    }

    protected static function booted(): void
    {
        static::saving(function (self $page): void {
            $page->slug = self::normalizeSlug($page->slug);
        });
    }
}
