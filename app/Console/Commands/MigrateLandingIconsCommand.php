<?php

namespace App\Console\Commands;

use App\Models\LandingBlock;
use App\Models\LandingSection;
use App\Services\LandingPageService;
use App\Support\LandingIcons;
use Illuminate\Console\Command;

class MigrateLandingIconsCommand extends Command
{
    protected $signature = 'landing:migrate-icons';

    protected $description = 'Convert legacy Figma icon URLs to local sprite keys';

    public function handle(LandingPageService $landing): int
    {
        $updated = 0;

        foreach (LandingSection::query()->cursor() as $section) {
            $changes = $this->migrateRecord($section);
            $updated += $this->applySectionChanges($section, $changes);
        }

        foreach (LandingBlock::query()->cursor() as $block) {
            if ($this->migrateValue($block->icon)) {
                $block->icon = LandingIcons::toStorage(LandingIcons::resolve($block->icon));
                $block->save();
                $updated++;
            }
        }

        $landing->clearCache();

        $this->info("Updated {$updated} icon references.");

        return self::SUCCESS;
    }

    /** @return array<string, mixed> */
    private function migrateRecord(LandingSection $section): array
    {
        return [
            'badge_icon' => $this->migrateValue($section->badge_icon),
            'extra' => $this->migrateExtra($section->extra ?? []),
        ];
    }

    /** @param array<string, mixed> $changes */
    private function applySectionChanges(LandingSection $section, array $changes): int
    {
        $updated = 0;

        if ($changes['badge_icon']) {
            $section->badge_icon = LandingIcons::toStorage(LandingIcons::resolve($section->badge_icon));
            $updated++;
        }

        if ($changes['extra']) {
            $section->extra = $this->migrateExtraArray($section->extra ?? []);
            $updated++;
        }

        if ($updated > 0) {
            $section->save();
        }

        return $updated;
    }

    /** @param array<string, mixed> $extra */
    private function migrateExtra(array $extra): bool
    {
        foreach ($extra as $value) {
            if (is_string($value) && $this->migrateValue($value)) {
                return true;
            }
        }

        return false;
    }

    /** @param array<string, mixed> $extra */
    private function migrateExtraArray(array $extra): array
    {
        foreach ($extra as $key => $value) {
            if (! is_string($value)) {
                continue;
            }

            $resolved = LandingIcons::resolve($value);

            if ($resolved) {
                $extra[$key] = LandingIcons::toStorage($resolved);
            }
        }

        return $extra;
    }

    private function migrateValue(?string $value): bool
    {
        if (blank($value)) {
            return false;
        }

        if (str_starts_with($value, 'icon:')) {
            return false;
        }

        return LandingIcons::resolve($value) !== null;
    }
}
