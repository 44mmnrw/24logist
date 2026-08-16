<?php

namespace App\Filament\Clusters\Landing\Resources\SeoResearchRuns\Pages;

use App\Filament\Clusters\Landing\Resources\SeoResearchRuns\SeoResearchRunResource;
use App\Services\Seo\WordstatCsvImporter;
use App\Services\Seo\YandexWordstatCollector;
use Filament\Actions\Action;
use Filament\Forms\Components\FileUpload;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Support\Facades\Storage;

class ListSeoResearchRuns extends ListRecords
{
    protected static string $resource = SeoResearchRunResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('refreshWordstat')
                ->label('Обновить Wordstat')
                ->icon('heroicon-o-arrow-path')
                ->color('success')
                ->requiresConfirmation()
                ->modalDescription('Wordstat будет обновлён для всех активных кластеров, у которых заполнена Seed-фраза.')
                ->action(function (YandexWordstatCollector $collector): void {
                    $run = $collector->collect();

                    Notification::make()
                        ->title($run->status === 'completed' ? 'Wordstat обновлён' : 'Wordstat обновлён с ошибками')
                        ->body("Обработано кластеров: {$run->processed_items} из {$run->total_items}. Запуск #{$run->getKey()}.")
                        ->color($run->status === 'completed' ? 'success' : 'warning')
                        ->send();
                }),
            Action::make('importWordstat')
                ->label('Импорт Wordstat CSV')
                ->icon('heroicon-o-arrow-up-tray')
                ->schema([
                    FileUpload::make('file')
                        ->label('CSV из Wordstat')
                        ->disk('local')
                        ->directory('wordstat/uploads')
                        ->acceptedFileTypes(['text/csv', 'text/plain', 'application/vnd.ms-excel'])
                        ->required(),
                ])
                ->action(function (array $data, WordstatCsvImporter $importer): void {
                    $run = $importer->import(Storage::disk('local')->path((string) $data['file']));

                    Notification::make()
                        ->title('Wordstat импортирован')
                        ->body("Обработано запросов: {$run->processed_items}. Запуск #{$run->getKey()}.")
                        ->success()
                        ->send();
                }),
        ];
    }
}
