<?php

namespace App\Filament\Resources\ReferralProgramSettings\Pages;

use App\Filament\Resources\ReferralProgramSettings\ReferralProgramSettingResource;
use App\Models\ReferralProgramSetting;
use Filament\Resources\Pages\EditRecord;

class EditReferralProgramSetting extends EditRecord
{
    protected static string $resource = ReferralProgramSettingResource::class;

    protected static ?string $title = 'Настройки реферальной программы';

    public function mount(int|string|null $record = null): void
    {
        parent::mount(ReferralProgramSetting::current()->getKey());
    }
}
