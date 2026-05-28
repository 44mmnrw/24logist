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
            $page->slug = Str::slug($page->slug);
        });
    }
}
