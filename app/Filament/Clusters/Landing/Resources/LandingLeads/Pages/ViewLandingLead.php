<?php

namespace App\Filament\Clusters\Landing\Resources\LandingLeads\Pages;

use App\Filament\Clusters\Landing\Resources\LandingLeads\LandingLeadResource;
use App\Models\LandingLead;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;

class ViewLandingLead extends ViewRecord
{
    protected static string $resource = LandingLeadResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('mark_processed')
                ->label('Отметить обработанной')
                ->visible(fn (): bool => $this->record->status === LandingLead::STATUS_NEW)
                ->action(function (): void {
                    $this->record->update(['status' => LandingLead::STATUS_PROCESSED]);
                    Notification::make()->title('Заявка отмечена как обработанная')->success()->send();
                }),
            Action::make('mark_new')
                ->label('Вернуть в «Новая»')
                ->visible(fn (): bool => $this->record->status === LandingLead::STATUS_PROCESSED)
                ->action(function (): void {
                    $this->record->update(['status' => LandingLead::STATUS_NEW]);
                    Notification::make()->title('Статус: новая')->success()->send();
                }),
        ];
    }
}
