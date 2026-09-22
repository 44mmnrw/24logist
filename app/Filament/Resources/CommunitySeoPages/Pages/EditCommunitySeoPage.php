<?php

namespace App\Filament\Resources\CommunitySeoPages\Pages;

use App\Filament\Resources\CommunitySeoPages\CommunitySeoPageResource;
use Filament\Actions\Action;
use Filament\Resources\Pages\EditRecord;

class EditCommunitySeoPage extends EditRecord
{
    protected static string $resource = CommunitySeoPageResource::class;

    protected function getHeaderActions(): array
    {
        return [Action::make('open')->label('Открыть страницу')->url(fn (): string => $this->getRecord()->getUrl())->openUrlInNewTab()];
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        $data['settings'] = array_replace(['include_in_sitemap' => true, 'schema_enabled' => true], $data['settings'] ?? []);

        return $data;
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        // The URL inventory is managed by the application, never by form input.
        return ['settings' => $data['settings'] ?? []];
    }
}
