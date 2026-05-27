<?php

namespace Tests\Unit;

use App\Integrations\YandexMetrika\MetrikaTrafficSourceMapper;
use App\Integrations\YandexMetrika\MetrikaVisitAttribution;
use App\Services\AdvertisingChannelResolver;
use PHPUnit\Framework\TestCase;

class MetrikaTrafficSourceMapperTest extends TestCase
{
    public function test_ad_traffic_source_maps_to_advertising(): void
    {
        $mapper = new MetrikaTrafficSourceMapper;

        $result = $mapper->toAdvertisingChannel(new MetrikaVisitAttribution(
            trafficSourceId: 'ad',
            trafficSourceName: 'Рекламный трафик',
            utmSource: null,
            utmMedium: null,
            utmCampaign: null,
            utmTerm: null,
            utmContent: null,
            utmCampaignFirst: null,
        ));

        $this->assertSame(AdvertisingChannelResolver::ADVERTISING, $result);
    }

    public function test_organic_maps_to_no_data(): void
    {
        $mapper = new MetrikaTrafficSourceMapper;

        $result = $mapper->toAdvertisingChannel(new MetrikaVisitAttribution(
            trafficSourceId: 'organic',
            trafficSourceName: 'Переходы из поисковых систем',
            utmSource: null,
            utmMedium: null,
            utmCampaign: null,
            utmTerm: null,
            utmContent: null,
            utmCampaignFirst: null,
        ));

        $this->assertSame(AdvertisingChannelResolver::NO_DATA, $result);
    }
}
