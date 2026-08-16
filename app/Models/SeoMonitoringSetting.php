<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SeoMonitoringSetting extends Model
{
    protected $fillable = [
        'yandex_api_key', 'yandex_folder_id', 'target_host', 'default_region_id',
        'default_device', 'position_depth', 'position_batch_limit', 'wordstat_limit',
    ];

    protected function casts(): array
    {
        return [
            'yandex_api_key' => 'encrypted',
            'position_depth' => 'integer',
            'position_batch_limit' => 'integer',
            'wordstat_limit' => 'integer',
        ];
    }

    public function hasYandexApiKey(): bool
    {
        return filled($this->attributes['yandex_api_key'] ?? null)
            || filled(config('seo-monitoring.yandex_api_key'));
    }

    public static function instance(): self
    {
        return static::query()->firstOrCreate(
            ['id' => 1],
            [
                'target_host' => (string) config('seo-monitoring.target_host', '24logist.ru'),
                'default_region_id' => (string) config('seo-monitoring.default_region_id', '225'),
                'default_device' => (string) config('seo-monitoring.default_device', 'DEVICE_ALL'),
                'position_depth' => (int) config('seo-monitoring.position_depth', 100),
                'position_batch_limit' => 5,
                'wordstat_limit' => 100,
            ],
        );
    }
}
