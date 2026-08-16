<?php

namespace App\Filament\Clusters\Landing\Resources\BlogTags\Pages;

use App\Filament\Clusters\Landing\Resources\BlogTags\BlogTagResource;
use App\Services\BlogTagSocialImageGenerator;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;

class EditBlogTag extends EditRecord
{
    protected static string $resource = BlogTagResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('generateSocialImage')
                ->label('Сгенерировать изображение')
                ->icon('heroicon-o-photo')
                ->action(function (): void {
                    app(BlogTagSocialImageGenerator::class)->generate($this->record);
                    $this->record->refresh();
                    $this->fillForm();

                    Notification::make()
                        ->title('Изображение тега создано')
                        ->success()
                        ->send();
                }),
            DeleteAction::make()
                ->visible(fn (): bool => ! $this->record->isUsed()),
        ];
    }
}
