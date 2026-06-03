<?php

namespace App\Filament\Clusters\Landing\Resources\SiteSettings\Pages;

use App\Filament\Clusters\Landing\Resources\SiteSettings\GeneralSiteSettingResource;
use App\Models\SiteSetting;
use App\Services\SiteSettingsService;
use App\Support\LandingMedia;
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
        if (filled($data['favicon_path'] ?? null)) {
            $data['favicon_path'] = [(string) $data['favicon_path']];
        }

        return $data;
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $data['favicon_path'] = $this->persistFavicon($data['favicon_path'] ?? null);

        return $data;
    }

    protected function afterSave(): void
    {
        app(SiteSettingsService::class)->clearCache();
    }

    private function persistFavicon(mixed $state): ?string
    {
        if ($state instanceof TemporaryUploadedFile) {
            return $state->store('site/favicon', 'public');
        }

        if (is_array($state)) {
            foreach ($state as $item) {
                $stored = $this->persistFavicon($item);

                if ($stored !== null) {
                    return $stored;
                }
            }

            return null;
        }

        return LandingMedia::normalizePath($state);
    }
}
