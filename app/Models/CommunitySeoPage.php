<?php

namespace App\Models;

use App\Services\SitemapService;
use Illuminate\Database\Eloquent\Model;

class CommunitySeoPage extends Model
{
    public const KINDS = [
        'page' => 'Страница',
        'category' => 'Рубрика',
        'post' => 'Тема',
        'profile' => 'Профиль',
        'service' => 'Служебная страница',
    ];

    protected $fillable = ['page_key', 'kind', 'label', 'path', 'is_public', 'settings'];

    protected function casts(): array
    {
        return ['is_public' => 'boolean', 'settings' => 'array'];
    }

    public function getUrl(): string
    {
        return url($this->path);
    }

    protected static function booted(): void
    {
        static::saved(fn () => app(SitemapService::class)->clearCache());
        static::deleted(fn () => app(SitemapService::class)->clearCache());
    }
}
