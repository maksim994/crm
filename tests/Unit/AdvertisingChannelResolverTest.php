<?php

namespace Tests\Unit;

use App\Enums\LeadChannel;
use App\Services\AdvertisingChannelResolver;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class AdvertisingChannelResolverTest extends TestCase
{
    private AdvertisingChannelResolver $resolver;

    protected function setUp(): void
    {
        parent::setUp();
        $this->resolver = new AdvertisingChannelResolver;
    }

    #[DataProvider('nonFormChannels')]
    public function test_non_form_channel_returns_no_data(LeadChannel $channel): void
    {
        $result = $this->resolver->resolve($channel, 'cpc', 'yandex', '123');

        $this->assertSame(AdvertisingChannelResolver::NO_DATA, $result);
    }

    public static function nonFormChannels(): array
    {
        return [
            [LeadChannel::Call],
            [LeadChannel::Email],
        ];
    }

    public function test_cpc_medium_returns_advertising(): void
    {
        $result = $this->resolver->resolve(LeadChannel::Form, 'cpc', null, null);

        $this->assertSame(AdvertisingChannelResolver::ADVERTISING, $result);
    }

    #[DataProvider('advertisingSources')]
    public function test_search_engine_source_returns_advertising(string $source): void
    {
        $result = $this->resolver->resolve(LeadChannel::Form, null, $source, null);

        $this->assertSame(AdvertisingChannelResolver::ADVERTISING, $result);
    }

    public static function advertisingSources(): array
    {
        return [
            ['yandex'],
            ['Yandex.Direct'],
            ['google'],
            ['www.google.com'],
        ];
    }

    public function test_organic_traffic_returns_no_data(): void
    {
        $result = $this->resolver->resolve(LeadChannel::Form, 'organic', 'newsletter', '999');

        $this->assertSame(AdvertisingChannelResolver::NO_DATA, $result);
    }
}
