<?php

namespace App\Filament\Resources\ReferralPayouts\Pages;

use App\Filament\Resources\ReferralPayouts\ReferralPayoutResource;
use App\Services\Referral\ReferralPayoutService;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;

class ListReferralPayouts extends ListRecords
{
    protected static string $resource = ReferralPayoutResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('createRegistry')->label('Сформировать месячный реестр')->action(function (): void {
                $count = app(ReferralPayoutService::class)->createMonthlyRegistry()->count();
                Notification::make()->title("Создано реестров: {$count}")->success()->send();
            }),
            Action::make('csv')->label('Экспорт CSV')->url('/admin/referrals/payouts/export.csv')->openUrlInNewTab(),
            Action::make('xlsx')->label('Экспорт XLSX')->url('/admin/referrals/payouts/export.xlsx')->openUrlInNewTab(),
        ];
    }
}
