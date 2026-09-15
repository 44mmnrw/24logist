<?php

namespace App\Filament\Resources\CommunitySettings\Pages;

use App\Filament\Resources\CommunitySettings\CommunitySettingResource;
use App\Models\SiteSetting;
use App\Services\Community\MaxWebhookSubscriptionService;
use App\Services\SiteSettingsService;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Throwable;

class EditCommunitySetting extends EditRecord
{
    private const SECRET_FIELDS = [
        'community_telegram_client_secret',
        'community_telegram_bot_token',
        'community_vk_client_secret',
        'community_vk_service_token',
        'community_max_bot_token',
        'community_max_webhook_secret',
        'community_ai_timeweb_token',
        'community_ai_collector_token',
    ];

    protected static string $resource = CommunitySettingResource::class;

    protected static ?string $title = 'Настройки сообщества';

    /** @var array<string, string> */
    private array $pendingSecrets = [];

    public function mount(int|string|null $record = null): void
    {
        parent::mount(SiteSetting::instance()->getKey());
    }

    public function saveAndRegisterMaxWebhook(): void
    {
        $this->save(shouldRedirect: false, shouldSendSavedNotification: false);

        try {
            $message = app(MaxWebhookSubscriptionService::class)->register();

            Notification::make()
                ->title('Webhook MAX подключён')
                ->body($message)
                ->success()
                ->send();
        } catch (Throwable $exception) {
            Notification::make()
                ->title('Не удалось подключить webhook MAX')
                ->body($exception->getMessage())
                ->danger()
                ->send();
        }
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        $data['community_enabled'] ??= false;
        $data['community_max_enabled'] ??= false;
        $data['community_vk_enabled'] ??= false;

        foreach (self::SECRET_FIELDS as $field) {
            $data[$field] = '';
        }

        return $data;
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        foreach (self::SECRET_FIELDS as $field) {
            if (filled($data[$field] ?? null)) {
                $this->pendingSecrets[$field] = (string) $data[$field];
            }

            unset($data[$field]);
        }

        return $data;
    }

    protected function afterSave(): void
    {
        if ($this->pendingSecrets !== []) {
            $this->record->fill($this->pendingSecrets)->save();
            $this->pendingSecrets = [];

            Notification::make()
                ->title('Секретные настройки сохранены')
                ->body('Сохранённые значения отображаются в форме как ***.')
                ->success()
                ->send();
        }

        app(SiteSettingsService::class)->clearCache();

        $this->record->refresh();
        $this->fillForm();
    }
}
