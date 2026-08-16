<?php

namespace App\Filament\Clusters\Landing\Resources\SeoMonitoringSettings\Pages;

use App\Filament\Clusters\Landing\Resources\SeoMonitoringSettings\SeoMonitoringSettingResource;
use App\Models\SeoMonitoringSetting;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;

class EditSeoMonitoringSetting extends EditRecord
{
    protected static string $resource = SeoMonitoringSettingResource::class;

    protected static ?string $title = 'Настройки SEO API';

    private ?string $pendingApiKey = null;

    public function mount(int|string|null $record = null): void
    {
        parent::mount(SeoMonitoringSetting::instance()->getKey());
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        $data['yandex_api_key'] = '';

        return $data;
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        if (filled($data['yandex_api_key'] ?? null)) {
            $this->pendingApiKey = (string) $data['yandex_api_key'];
        }

        unset($data['yandex_api_key']);

        return $data;
    }

    protected function afterSave(): void
    {
        if ($this->pendingApiKey !== null) {
            $this->record->yandex_api_key = $this->pendingApiKey;
            $this->record->save();
            $this->pendingApiKey = null;
            Notification::make()->title('API-ключ сохранён в зашифрованном виде')->success()->send();
        }

        $this->record->refresh();
        $this->fillForm();
    }
}
