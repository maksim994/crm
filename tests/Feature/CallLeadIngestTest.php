<?php

namespace Tests\Feature;

use App\Enums\AgencyClientStatus;
use App\Enums\LeadChannel;
use App\Enums\SiteStatus;
use App\Models\AgencyClient;
use App\Models\Lead;
use App\Models\Site;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CallLeadIngestTest extends TestCase
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

    public function test_call_webhook_creates_lead_with_normalized_payload(): void
    {
        $this->postJson('/api/v1/leads/call?token='.$this->token, [
            'phone' => '+79001112233',
            'call_recording_url' => 'https://provider.example/record/1',
            'call_duration_sec' => 95,
            'utm_campaign' => 'cid|123|search',
        ])
            ->assertCreated()
            ->assertJsonStructure(['id', 'is_duplicate']);

        $this->assertDatabaseHas('leads', [
            'site_id' => $this->site->id,
            'phone' => '+79001112233',
            'channel' => LeadChannel::Call->value,
            'call_recording_url' => 'https://provider.example/record/1',
            'call_duration_sec' => 95,
        ]);
    }

    public function test_callibri_payload_is_mapped(): void
    {
        $this->postJson('/api/v1/leads/call', [
            'caller_phone' => '+79009998877',
            'call_time' => '2026-05-18T14:30:00+03:00',
            'campaign' => 'yandex-cpc',
            'record_url' => 'https://callibri.example/rec.mp3',
            'duration' => 42,
        ], [
            'X-Site-Token' => $this->token,
        ])
            ->assertCreated();

        $lead = Lead::query()->where('phone', '+79009998877')->first();
        $this->assertNotNull($lead);
        $this->assertSame(LeadChannel::Call, $lead->channel);
        $this->assertSame('https://callibri.example/rec.mp3', $lead->call_recording_url);
        $this->assertSame(42, $lead->call_duration_sec);
        $this->assertSame('yandex-cpc', $lead->utm_campaign);
    }

    public function test_call_without_token_returns_401(): void
    {
        $this->postJson('/api/v1/leads/call', [
            'phone' => '+79001112233',
        ])->assertUnauthorized();
    }
}
