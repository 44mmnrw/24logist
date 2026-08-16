<?php

namespace App\Filament\Clusters\Landing\Resources\BlogTags\Pages;

use App\Filament\Clusters\Landing\Resources\BlogTags\BlogTagResource;
use App\Models\BlogTag;
use App\Services\BlogTagSocialImageGenerator;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;

class ListBlogTags extends ListRecords
{
    protected static string $resource = BlogTagResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('generateSocialImages')
                ->label('Сгенерировать изображения')
                ->icon('heroicon-o-photo')
                ->requiresConfirmation()
                ->modalDescription('Обложки всех тегов будут созданы заново, а их OG, Twitter и Schema изображения — обновлены.')
                ->action(function (): void {
                    $generator = app(BlogTagSocialImageGenerator::class);
                    $count = 0;

                    BlogTag::query()->orderBy('id')->each(function (BlogTag $tag) use ($generator, &$count): void {
                        $generator->generate($tag);
                        $count++;
                    });

                    Notification::make()
                        ->title('Изображения тегов созданы')
                        ->body('Обработано тегов: '.$count)
                        ->success()
                        ->send();
                }),
            CreateAction::make(),
        ];
    }
}
