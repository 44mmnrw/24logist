<?php

namespace App\Filament\Clusters\Landing\Resources\BlogTags\Pages;

use App\Filament\Clusters\Landing\Resources\BlogTags\BlogTagResource;
use App\Services\BlogTagSocialImageGenerator;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Throwable;

class CreateBlogTag extends CreateRecord
{
    protected static string $resource = BlogTagResource::class;

    protected function afterCreate(): void
    {
        try {
            app(BlogTagSocialImageGenerator::class)->generate($this->record);
        } catch (Throwable $exception) {
            report($exception);

            Notification::make()
                ->title('Тег создан без изображения')
                ->body('Обложку можно создать позднее на странице редактирования тега.')
                ->warning()
                ->send();
        }
    }
}
