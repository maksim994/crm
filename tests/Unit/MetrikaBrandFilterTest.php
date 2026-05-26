<?php

namespace Tests\Unit;

use App\Integrations\YandexMetrika\MetrikaBrandFilter;
use App\Models\Site;
use Tests\TestCase;

class MetrikaBrandFilterTest extends TestCase
{
    public function test_branded_filter_uses_search_phrase_regex(): void
    {
        $site = new Site([
            'metrika_brand_keywords' => ['ruflex', 'руфлекс'],
        ]);

        $filter = MetrikaBrandFilter::organicSearchFilter($site, true);

        $this->assertStringContainsString("ym:s:trafficSource=='organic'", $filter);
        $this->assertStringContainsString("ym:s:searchPhrase=~'", $filter);
        $this->assertStringContainsString('ruflex', $filter);
        $this->assertStringContainsString('руфлекс', $filter);
    }

    public function test_non_branded_filter_negates_search_phrase_regex(): void
    {
        $site = new Site([
            'metrika_brand_keywords' => ['brand'],
        ]);

        $filter = MetrikaBrandFilter::organicSearchFilter($site, false);

        $this->assertStringContainsString("ym:s:searchPhrase!~'", $filter);
    }
}
