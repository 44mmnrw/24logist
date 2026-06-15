<?php

namespace App\Filament\Clusters\Landing\Resources\SiteSettings\Pages;

use App\Filament\Clusters\Landing\Resources\SiteSettings\GeneralSiteSettingResource;
use App\Models\SiteSetting;
use App\Services\SiteMailService;
use App\Services\SiteSettingsService;
use App\Support\AppleTouchIcon;
use App\Support\FilamentMediaUpload;
use App\Support\PwaIcons;
use Filament\Actions\Action;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Throwable;

class EditGeneralSiteSetting extends EditRecord
{
    protected static string $resource = GeneralSiteSettingResource::class;

    protected static ?string $title = 'Настройки сайта';

    private bool $mailPasswordUpdated = false;

    private ?string $pendingMailPassword = null;

    public function mount(int|string|null $record = null): void
    {
        parent::mount(SiteSetting::instance()->getKey());
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('sendTestMail')
                ->label('Тестовое письмо')
                ->icon('heroicon-o-paper-airplane')
                ->color('gray')
                ->modalHeading('Отправка тестового письма')
                ->modalDescription('Проверьте SMTP: письмо уйдёт с текущими настройками. Сначала сохраните форму, если меняли поля.')
                ->schema([
                    TextInput::make('test_email')
                        ->label('Email получателя')
                        ->email()
                        ->required()
                        ->default(fn (): ?string => auth()->user()?->email),
                ])
                ->action(function (array $data, SiteMailService $mail): void {
                    try {
                        $mail->sendTest((string) $data['test_email']);

                        Notification::make()
                            ->title('Тестовое письмо отправлено')
                            ->body('Проверьте почтовый ящик '.$data['test_email'].'.')
                            ->success()
                            ->send();
                    } catch (Throwable $exception) {
                        $site = SiteSetting::query()->find(SiteSetting::instance()->getKey());
                        $details = $site ? ' SMTP: '.$site->mail_host.':'.$site->mail_port
                            .', логин: '.($site->mail_username ?: '—')
                            .', пароль в базе: '.($site->hasMailPassword() ? 'да' : 'нет').'.' : '';

                        Notification::make()
                            ->title('Не удалось отправить письмо')
                            ->body($exception->getMessage().$details)
                            ->danger()
                            ->send();
                    }
                }),
        ];
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        foreach (['favicon_path', 'apple_touch_icon_path', 'og_image_path', 'org_logo_path'] as $field) {
            if (filled($data[$field] ?? null)) {
                $data[$field] = [(string) $data[$field]];
            }
        }

        $data['mail_password'] = '';

        return $data;
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $data['favicon_path'] = $this->persistUpload($data['favicon_path'] ?? null, 'site/favicon');
        $data['apple_touch_icon_path'] = $this->persistUpload($data['apple_touch_icon_path'] ?? null, 'site/apple-touch-icon');
        $data['og_image_path'] = $this->persistUpload($data['og_image_path'] ?? null, 'site/og');
        $data['org_logo_path'] = $this->persistUpload($data['org_logo_path'] ?? null, 'site/org');

        if (filled($data['mail_password'] ?? null)) {
            $this->pendingMailPassword = (string) $data['mail_password'];
        }

        unset($data['mail_password']);

        return $data;
    }

    protected function afterSave(): void
    {
        foreach ([AppleTouchIcon::cachePath(), ...array_map(PwaIcons::cachePath(...), PwaIcons::SIZES)] as $cache) {
            if (is_file($cache)) {
                unlink($cache);
            }
        }

        if ($this->pendingMailPassword !== null) {
            $this->record->mail_password = $this->pendingMailPassword;
            $this->record->save();
            $this->mailPasswordUpdated = true;
            $this->pendingMailPassword = null;
        }

        app(SiteSettingsService::class)->clearCache();

        if ($this->mailPasswordUpdated) {
            Notification::make()
                ->title('Пароль почты сохранён')
                ->body('В поле пароль не показывается — это нормально. Для отправки используется значение из базы.')
                ->success()
                ->send();

            $this->mailPasswordUpdated = false;
        }

        $this->record->refresh();
        $this->fillForm();
    }

    private function persistUpload(mixed $state, string $directory): ?string
    {
        return FilamentMediaUpload::persist($state, $directory);
    }
}
