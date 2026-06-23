<?php

namespace App\Filament\Clusters\Landing\Resources\BlogCategories\Pages;

use App\Filament\Clusters\Landing\Resources\BlogCategories\BlogCategoryResource;
use Filament\Resources\Pages\CreateRecord;

class CreateBlogCategory extends CreateRecord
{
    protected static string $resource = BlogCategoryResource::class;
}
