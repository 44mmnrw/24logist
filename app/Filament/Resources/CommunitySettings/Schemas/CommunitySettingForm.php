<?php

namespace App\Filament\Resources\CommunitySettings\Schemas;

use App\Filament\Clusters\Landing\Resources\SiteSettings\Schemas\GeneralSiteSettingForm;

final class CommunitySettingForm
{
    /**
     * @return array<int, mixed>
     */
    public static function components(): array
    {
        return GeneralSiteSettingForm::communityComponents();
    }
}
