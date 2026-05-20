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
                            ['id' => 'campaign-1', 'name' => 'spring-sale'],
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
        $this->assertSame('spring-sale', $lead->utm_campaign_first);
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
