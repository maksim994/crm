<?php

namespace Tests\Feature;

use App\Enums\LeadChannel;
use App\Enums\LeadStatus;
use App\Enums\SiteStatus;
use App\Enums\AgencyClientStatus;
use App\Models\AgencyClient;
use App\Models\Lead;
use App\Models\Site;
use App\Services\AdvertisingChannelResolver;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SeoLeadIngestTest extends TestCase
{
    use RefreshDatabase;

    private string $token;

    private Site $site;

    protected function setUp(): void
    {
        parent::setUp();

        $client = AgencyClient::query()->create([
            'name' => 'Test Client',
            'status' => AgencyClientStatus::Active,
        ]);

        $this->site = Site::query()->create([
            'agency_client_id' => $client->id,
            'name' => 'Test Site',
            'domains' => ['example.com'],
            'timezone' => 'Europe/Moscow',
            'token_hash' => '',
            'status' => SiteStatus::Active,
        ]);

        $this->token = $this->site->issueToken();
    }

    public function test_valid_token_creates_lead(): void
    {
        $response = $this->post('/ingest/seolead', [
            'token' => $this->token,
            'phone' => '+79001112233',
            'description' => 'Тест',
            'metrika_client_id' => '17791064241773632',
            'utm_source' => 'yandex',
            'utm_medium' => 'cpc',
            'page_url' => 'https://example.com/landing',
        ]);

        $response->assertCreated()
            ->assertJsonStructure(['id']);

        $this->assertDatabaseHas('leads', [
            'site_id' => $this->site->id,
            'phone' => '+79001112233',
            'form_description' => 'Тест',
            'lead_status' => LeadStatus::NotProcessed->value,
            'channel' => LeadChannel::Form->value,
            'advertising_channel' => AdvertisingChannelResolver::ADVERTISING,
            'landing_domain' => 'example.com',
            'is_duplicate' => false,
        ]);
    }

    public function test_invalid_token_returns_401(): void
    {
        $this->post('/ingest/seolead', [
            'token' => $this->site->id.':wrong-secret',
            'phone' => '+79001112233',
        ])->assertUnauthorized();

        $this->assertDatabaseCount('leads', 0);
    }

    public function test_paused_site_returns_403(): void
    {
        $this->site->update(['status' => SiteStatus::Paused]);

        $this->post('/ingest/seolead', [
            'token' => $this->token,
            'phone' => '+79001112233',
        ])->assertForbidden();
    }

    public function test_duplicate_phone_is_marked_within_30_days(): void
    {
        Lead::query()->create([
            'site_id' => $this->site->id,
            'channel' => LeadChannel::Form,
            'phone' => '+7 (900) 111-22-33',
            'lead_status' => LeadStatus::NotProcessed,
            'created_at' => now()->subDays(5),
        ]);

        $this->post('/ingest/seolead', [
            'token' => $this->token,
            'phone' => '8 (900) 111-22-33',
        ])->assertCreated();

        $this->assertDatabaseHas('leads', [
            'site_id' => $this->site->id,
            'phone' => '8 (900) 111-22-33',
            'is_duplicate' => true,
        ]);
    }

    public function test_get_request_is_supported(): void
    {
        $this->get('/ingest/seolead?'.http_build_query([
            'token' => $this->token,
            'phone' => '+79009998877',
        ]))->assertCreated();
    }

    public function test_plain_text_response_when_accepted(): void
    {
        $response = $this->post('/ingest/seolead', [
            'token' => $this->token,
            'phone' => '+79005554433',
        ], ['Accept' => 'text/plain']);

        $response->assertCreated();
        $this->assertMatchesRegularExpression(
            '/^[0-9a-f-]{36}$/i',
            trim($response->getContent()),
        );
    }
}
