<?php

namespace App\Filament\Clusters\Landing\Resources\SeoKeywords\Pages;

use App\Filament\Clusters\Landing\Resources\SeoKeywords\SeoKeywordResource;
use App\Models\SeoMonitoringSetting;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Support\Facades\Artisan;

class ListSeoKeywords extends ListRecords
{
    protected static string $resource = SeoKeywordResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('checkPositions')
                ->label('Проверить позиции')
                ->icon('heroicon-o-arrow-path')
                ->color('success')
                ->schema([
                    Select::make('limit')
                        ->label('Количество запросов')
                        ->options([1 => '1', 5 => '5', 10 => '10', 25 => '25', 50 => '50'])
                        ->default(fn (): int => SeoMonitoringSetting::instance()->position_batch_limit)
                        ->required()
                        ->helperText('Проверяются запросы, которые обновлялись раньше остальных. Каждый запрос расходует квоту Search API.'),
                ])
                ->action(function (array $data): void {
                    $exitCode = Artisan::call('seo:check-positions', ['--limit' => (int) $data['limit']]);

                    Notification::make()
                        ->title($exitCode === 0 ? 'Позиции обновлены' : 'Проверка завершена с ошибками')
                        ->body(mb_substr(trim(Artisan::output()), 0, 1000))
                        ->color($exitCode === 0 ? 'success' : 'warning')
                        ->send();
                }),
            CreateAction::make(),
        ];
    }
}
