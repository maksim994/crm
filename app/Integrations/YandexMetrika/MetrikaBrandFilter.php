<?php

namespace App\Integrations\YandexMetrika;

use App\Exceptions\Client\MetrikaAnalyticsUnavailableException;
use App\Models\Site;

class MetrikaBrandFilter
{
    public static function organicSearchFilter(Site $site, bool $branded): string
    {
        $keywords = self::keywords($site);

        if ($keywords === []) {
            throw new MetrikaAnalyticsUnavailableException(
                'Укажите ключевые слова бренда в настройках проекта (админка → проект → «Ключевые слова бренда»).',
            );
        }

        $pattern = self::regexPattern($keywords);
        $phraseFilter = $branded
            ? "ym:s:searchPhrase=~'{$pattern}'"
            : "ym:s:searchPhrase!~'{$pattern}'";

        return "ym:s:trafficSource=='organic' AND {$phraseFilter}";
    }

    /**
     * @return list<string>
     */
    public static function keywords(Site $site): array
    {
        $raw = $site->metrika_brand_keywords;

        if (! is_array($raw)) {
            return [];
        }

        $keywords = [];

        foreach ($raw as $keyword) {
            if (! is_string($keyword)) {
                continue;
            }

            $keyword = trim($keyword);

            if ($keyword !== '') {
                $keywords[] = $keyword;
            }
        }

        return array_values(array_unique($keywords));
    }

    /**
     * @param  list<string>  $keywords
     */
    private static function regexPattern(array $keywords): string
    {
        $parts = array_map(static function (string $keyword): string {
            $escaped = preg_quote($keyword, '/');

            return str_replace(["\\", "'"], ['\\\\', "\\'"], $escaped);
        }, $keywords);

        return '('.implode('|', $parts).')';
    }
}
