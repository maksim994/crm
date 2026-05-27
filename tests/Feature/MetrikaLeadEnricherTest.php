<?php

namespace Tests\Feature;

use App\Enums\AgencyClientStatus;
use App\Enums\LeadChannel;
use App\Enums\LeadStatus;
use App\Enums\SiteStatus;
use App\Integrations\YandexMetrika\MetrikaReportingClient;
use App\Jobs\EnrichLeadFromMetrikaJob;
use App\Models\AgencyClient;
use App\Models\Lead;
use App\Models\Site;
use App\Services\AdvertisingChannelResolver;
use App\Services\MetrikaLeadEnricher;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class MetrikaLeadEnricherTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Config::set('metrika.reporting_enabled', true);
        Config::set('metrika.oauth_token', 'test-oauth-token');
    }

    public function test_ingest_dispatches_metrika_enrichment_job(): void
    {
        Queue::fake();

        $site = $this->createSiteWithMetrika('57691633');
        $token = $site->issueToken();

        $this->post('/ingest/seolead', [
            'token' => $token,
            'phone' => '+79001112233',
            'metrika_client_id' => '17791064241773632',
        ])->assertCreated();

        Queue::assertPushed(EnrichLeadFromMetrikaJob::class);
    }

    public function test_enricher_updates_advertising_channel_from_metrika(): void
    {
        $site = $this->createSiteWithMetrika('57691633');
        $lead = Lead::query()->create([
            'site_id' => $site->id,
            'channel' => LeadChannel::Form,
            'phone' => '+79001112233',
            'lead_status' => LeadStatus::NotProcessed,
            'metrika_client_id' => '17791064241773632',
            'advertising_channel' => AdvertisingChannelResolver::NO_DATA,
            'is_duplicate' => false,
            'created_at' => now(),
        ]);

        Http::fake([
            'api-metrika.yandex.net/*' => Http::response([
                'data' => [
                    [
                        'dimensions' => [
                            ['id' => 'ad', 'name' => 'Рекламный трафик'],
                            ['id' => 'yandex', 'name' => 'yandex'],
                            ['id' => 'cpc', 'name' => 'cpc'],
                            ['id' => 'spring-sale', 'name' => 'spring-sale'],
                            ['id' => 'keyword', 'name' => 'keyword'],
                            ['id' => 'banner', 'name' => 'banner'],
                            ['id' => 'first-campaign', 'name' => 'first-campaign'],
                        ],
                        'metrics' => [1],
                    ],
                ],
            ]),
        ]);

        $enriched = app(MetrikaLeadEnricher::class)->enrich($lead->id);

        $this->assertTrue($enriched);
        $lead->refresh();
        $this->assertSame(AdvertisingChannelResolver::ADVERTISING, $lead->advertising_channel);
        $this->assertSame('yandex', $lead->utm_source);
        $this->assertSame('cpc', $lead->utm_medium);
        $this->assertSame('spring-sale', $lead->utm_campaign);
        $this->assertSame('keyword', $lead->utm_term);
        $this->assertSame('banner', $lead->utm_content);
        $this->assertSame('first-campaign', $lead->utm_campaign_first);
    }

    public function test_enricher_skips_when_not_configured(): void
    {
        Config::set('metrika.reporting_enabled', false);

        $site = $this->createSiteWithMetrika('57691633');
        $lead = Lead::query()->create([
            'site_id' => $site->id,
            'channel' => LeadChannel::Form,
            'phone' => '+79001112233',
            'lead_status' => LeadStatus::NotProcessed,
            'metrika_client_id' => '17791064241773632',
            'advertising_channel' => AdvertisingChannelResolver::NO_DATA,
            'is_duplicate' => false,
            'created_at' => now(),
        ]);

        Http::fake();

        $this->assertFalse(app(MetrikaReportingClient::class)->isConfigured());
        $this->assertFalse(app(MetrikaLeadEnricher::class)->enrich($lead->id));
        Http::assertNothingSent();
    }

    public function test_enricher_does_not_overwrite_existing_utm_fields(): void
    {
        $site = $this->createSiteWithMetrika('57691633');
        $lead = Lead::query()->create([
            'site_id' => $site->id,
            'channel' => LeadChannel::Form,
            'phone' => '+79001112233',
            'lead_status' => LeadStatus::NotProcessed,
            'metrika_client_id' => '17791064241773632',
            'utm_source' => 'form-source',
            'utm_medium' => 'form-medium',
            'advertising_channel' => AdvertisingChannelResolver::NO_DATA,
            'is_duplicate' => false,
            'created_at' => now(),
        ]);

        Http::fake([
            'api-metrika.yandex.net/*' => Http::response([
                'data' => [
                    [
                        'dimensions' => [
                            ['id' => 'ad', 'name' => 'Рекламный трафик'],
                            ['id' => 'yandex', 'name' => 'yandex'],
                            ['id' => 'cpc', 'name' => 'cpc'],
                            ['id' => 'spring-sale', 'name' => 'spring-sale'],
                            ['id' => 'keyword', 'name' => 'keyword'],
                            ['id' => 'banner', 'name' => 'banner'],
                            ['id' => 'first-campaign', 'name' => 'first-campaign'],
                        ],
                        'metrics' => [1],
                    ],
                ],
            ]),
        ]);

        app(MetrikaLeadEnricher::class)->enrich($lead->id);

        $lead->refresh();
        $this->assertSame('form-source', $lead->utm_source);
        $this->assertSame('form-medium', $lead->utm_medium);
        $this->assertSame('spring-sale', $lead->utm_campaign);
        $this->assertSame(AdvertisingChannelResolver::ADVERTISING, $lead->advertising_channel);
    }

    private function createSiteWithMetrika(string $counterId): Site
    {
        $client = AgencyClient::query()->create([
            'name' => 'Test Client',
            'status' => AgencyClientStatus::Active,
        ]);

        return Site::query()->create([
            'agency_client_id' => $client->id,
            'name' => 'Test Site',
            'domains' => ['example.com'],
            'metrika_counter_id' => $counterId,
            'timezone' => 'Europe/Moscow',
            'token_hash' => '',
            'status' => SiteStatus::Active,
        ]);
    }
}
