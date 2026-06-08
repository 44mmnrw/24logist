<?php

namespace App\Filament\Clusters\Landing\Resources\SiteSettings\Pages;

use App\Filament\Clusters\Landing\Resources\SiteSettings\GeneralSiteSettingResource;
use App\Models\SiteSetting;
use App\Services\SiteSettingsService;
use App\Support\AppleTouchIcon;
use App\Support\LandingMedia;
use App\Support\PwaIcons;
use Filament\Resources\Pages\EditRecord;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;

class EditGeneralSiteSetting extends EditRecord
{
    protected static string $resource = GeneralSiteSettingResource::class;

    protected static ?string $title = 'Настройки сайта';

    public function mount(int|string|null $record = null): void
    {
        parent::mount(SiteSetting::instance()->getKey());
    }

    protected function getHeaderActions(): array
    {
        return [];
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        foreach (['favicon_path', 'apple_touch_icon_path', 'og_image_path'] as $field) {
            if (filled($data[$field] ?? null)) {
                $data[$field] = [(string) $data[$field]];
            }
        }

        return $data;
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $data['favicon_path'] = $this->persistUpload($data['favicon_path'] ?? null, 'site/favicon');
        $data['apple_touch_icon_path'] = $this->persistUpload($data['apple_touch_icon_path'] ?? null, 'site/apple-touch-icon');
        $data['og_image_path'] = $this->persistUpload($data['og_image_path'] ?? null, 'site/og');

        return $data;
    }

    protected function afterSave(): void
    {
        foreach ([AppleTouchIcon::cachePath(), ...array_map(PwaIcons::cachePath(...), PwaIcons::SIZES)] as $cache) {
            if (is_file($cache)) {
                unlink($cache);
            }
        }

        app(SiteSettingsService::class)->clearCache();
    }

    private function persistUpload(mixed $state, string $directory): ?string
    {
        if ($state instanceof TemporaryUploadedFile) {
            return $state->store($directory, 'public');
        }

        if (is_array($state)) {
            foreach ($state as $item) {
                $stored = $this->persistUpload($item, $directory);

                if ($stored !== null) {
                    return $stored;
                }
            }

            return null;
        }

        return LandingMedia::normalizePath($state);
    }
}
