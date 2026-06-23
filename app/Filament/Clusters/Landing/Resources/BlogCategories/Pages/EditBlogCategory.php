<?php

namespace App\Filament\Clusters\Landing\Resources\BlogCategories\Pages;

use App\Filament\Clusters\Landing\Resources\BlogCategories\BlogCategoryResource;
use Filament\Resources\Pages\EditRecord;

class EditBlogCategory extends EditRecord
{
    protected static string $resource = BlogCategoryResource::class;
}
