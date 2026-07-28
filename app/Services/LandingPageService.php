<?php

namespace App\Services;

use App\Models\LandingBlock;
use App\Models\LandingSection;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

class LandingPageService
{
    private const CACHE_KEY = 'landing.page.content.v3';

    /** @var array<string, LandingSection>|null */
    private ?array $sections = null;

    public function sections(): Collection
    {
        return $this->load()->values();
    }

    public function section(string $slug): ?LandingSection
    {
        return $this->load()[$slug] ?? null;
    }

    public function blocks(string $slug, ?string $type = null): Collection
    {
        $section = $this->section($slug);

        if (! $section) {
            return collect();
        }

        $blocks = $section->blocks->where('is_active', true);

        if ($type !== null) {
            $blocks = $blocks->where('block_type', $type);
        }

        return $blocks->values();
    }

    public function clearCache(): void
    {
        Cache::forget(self::CACHE_KEY);
        Cache::forget('landing.page.content');
        app(PublicPageCache::class)->forgetLanding();
        $this->sections = null;
    }

    /** @return Collection<string, LandingSection> */
    private function load(): Collection
    {
        if ($this->sections !== null) {
            return collect($this->sections);
        }

        $payload = $this->readCachePayload();

        if ($payload === null) {
            $payload = $this->buildCachePayload();
            Cache::put(self::CACHE_KEY, json_encode($payload, JSON_THROW_ON_ERROR), now()->addHour());
        }

        $this->sections = collect($payload)
            ->mapWithKeys(fn (array $data, string $slug): array => [$slug => $this->sectionFromCache($data)])
            ->all();

        return collect($this->sections);
    }

    /** @return array<string, array{section: array<string, mixed>, blocks: array<int, array{block: array<string, mixed>, children: array<int, array<string, mixed>>}>}>|null */
    private function readCachePayload(): ?array
    {
        $cached = Cache::get(self::CACHE_KEY);

        if ($cached === null) {
            return null;
        }

        if (is_string($cached)) {
            try {
                $payload = json_decode($cached, true, 512, JSON_THROW_ON_ERROR);
            } catch (\JsonException) {
                return null;
            }
        } elseif (is_array($cached)) {
            $payload = $cached;
        } else {
            return null;
        }

        return $this->isValidPayload($payload) ? $payload : null;
    }

    /** @return array<string, array{section: array<string, mixed>, blocks: array<int, array{block: array<string, mixed>, children: array<int, array<string, mixed>>}>}> */
    private function buildCachePayload(): array
    {
        return collect($this->querySections())
            ->map(fn (LandingSection $section): array => $this->sectionToCache($section))
            ->all();
    }

    private function isValidPayload(mixed $payload): bool
    {
        if (! is_array($payload)) {
            return false;
        }

        foreach ($payload as $data) {
            if (! is_array($data) || ! isset($data['section'], $data['blocks']) || ! is_array($data['section'])) {
                return false;
            }
        }

        return true;
    }

    /** @return array<string, LandingSection> */
    private function querySections(): array
    {
        return LandingSection::query()
            ->where('is_active', true)
            ->with(['blocks' => fn ($query) => $query
                ->where('is_active', true)
                ->with(['children' => fn ($childQuery) => $childQuery
                    ->where('is_active', true)
                    ->orderBy('sort_order'),
                ])
                ->orderBy('sort_order'),
            ])
            ->orderBy('sort_order')
            ->get()
            ->keyBy('slug')
            ->all();
    }

    /** @return array{section: array<string, mixed>, blocks: array<int, array{block: array<string, mixed>, children: array<int, array<string, mixed>>}>} */
    private function sectionToCache(LandingSection $section): array
    {
        return [
            'section' => $section->getAttributes(),
            'blocks' => $section->blocks->map(fn (LandingBlock $block): array => [
                'block' => $block->getAttributes(),
                'children' => $block->children->map->getAttributes()->values()->all(),
            ])->values()->all(),
        ];
    }

    /** @param array{section: array<string, mixed>, blocks: array<int, array{block: array<string, mixed>, children: array<int, array<string, mixed>>}>} $data */
    private function sectionFromCache(array $data): LandingSection
    {
        $section = (new LandingSection)->newFromBuilder($this->attributesForBuilder($data['section']));

        $blocks = collect($data['blocks'])->map(function (array $blockData): LandingBlock {
            $block = (new LandingBlock)->newFromBuilder($this->attributesForBuilder($blockData['block']));
            $children = collect($blockData['children'])->map(
                fn (array $attributes): LandingBlock => (new LandingBlock)->newFromBuilder($this->attributesForBuilder($attributes)),
            );
            $block->setRelation('children', $children);

            return $block;
        });

        $section->setRelation('blocks', $blocks);

        return $section;
    }

    /**
     * @param  array<string, mixed>  $attributes
     * @return array<string, mixed>
     */
    private function attributesForBuilder(array $attributes): array
    {
        if (isset($attributes['extra']) && is_array($attributes['extra'])) {
            $attributes['extra'] = json_encode($attributes['extra'], JSON_THROW_ON_ERROR);
        }

        return $attributes;
    }
}
