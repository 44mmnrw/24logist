<?php

namespace App\Filament\Resources\CommunityAiPersonas\Pages;

use App\Filament\Resources\CommunityAiPersonas\CommunityAiPersonaResource;
use App\Services\Community\CommunityAiPersonaPromptBuilder;
use App\Services\Community\CommunityAvatarService;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Validation\ValidationException;

class EditCommunityAiPersona extends EditRecord
{
    protected static string $resource = CommunityAiPersonaResource::class;

    private ?string $previousAvatarPath = null;

    protected function beforeSave(): void
    {
        $this->previousAvatarPath = $this->record->communityUser?->avatar_path;
    }

    /** @param array<string, mixed> $data */
    protected function mutateFormDataBeforeSave(array $data): array
    {
        $settings = array_replace_recursive($this->record->settings ?? [], $data['settings'] ?? []);
        $errorChance = (int) data_get($settings, 'literacy_profile.error_chance', 0);
        $casualChance = (int) data_get($settings, 'literacy_profile.casual_chance', 0);

        if (($errorChance + $casualChance) > 100) {
            throw ValidationException::withMessages([
                'data.settings.literacy_profile.casual_chance' => 'Сумма разговорного режима и вероятности ошибки не должна превышать 100%.',
            ]);
        }

        $data['settings'] = $settings;
        $data['prompt_version'] = ((int) $this->record->prompt_version) + 1;

        return $data;
    }

    protected function afterSave(): void
    {
        $persona = $this->record->fresh('communityUser');

        $persona->forceFill([
            'system_prompt' => app(CommunityAiPersonaPromptBuilder::class)->build($persona),
        ])->saveQuietly();

        $currentAvatarPath = $persona->communityUser?->avatar_path;
        if ($this->previousAvatarPath !== $currentAvatarPath) {
            app(CommunityAvatarService::class)->deletePath($this->previousAvatarPath);
        }
    }
}
