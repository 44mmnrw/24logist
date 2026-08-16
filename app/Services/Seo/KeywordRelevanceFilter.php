<?php

namespace App\Services\Seo;

use App\Models\SeoKeyword;
use App\Models\SeoKeywordCluster;

class KeywordRelevanceFilter
{
    public function matches(SeoKeywordCluster $cluster, string $phrase): bool
    {
        $filter = (array) config('seo-monitoring.keyword_filters.'.$cluster->slug, []);

        if ($filter === []) {
            return false;
        }

        $normalized = str_replace('ё', 'е', SeoKeyword::normalizePhrase($phrase));
        $contains = static fn (string $term): bool => str_contains(
            $normalized,
            str_replace('ё', 'е', SeoKeyword::normalizePhrase($term)),
        );

        if (collect((array) ($filter['exclude_any'] ?? []))->contains($contains)) {
            return false;
        }

        $patterns = array_filter((array) ($filter['include_patterns'] ?? []));

        if ($patterns !== [] && ! collect($patterns)->contains(
            static fn (string $pattern): bool => preg_match($pattern, $normalized) === 1,
        )) {
            return false;
        }

        foreach ((array) ($filter['required_groups'] ?? []) as $group) {
            if (! collect((array) $group)->contains($contains)) {
                return false;
            }
        }

        return true;
    }
}
