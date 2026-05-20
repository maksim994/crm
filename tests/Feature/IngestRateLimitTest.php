<?php

namespace Tests\Feature;

use App\Enums\AgencyClientStatus;
use App\Enums\SiteStatus;
use App\Models\AgencyClient;
use App\Models\Site;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class IngestRateLimitTest extends TestCase
{
    use RefreshDatabase;

    public function test_ingest_returns_429_after_sixty_requests_per_minute(): void
    {
        $client = AgencyClient::query()->create([
            'name' => 'Rate Client',
            'status' => AgencyClientStatus::Active,
        ]);

        $site = Site::query()->create([
            'agency_client_id' => $client->id,
            'name' => 'Rate Site',
            'domains' => ['rate.example.com'],
            'timezone' => 'Europe/Moscow',
            'token_hash' => '',
            'status' => SiteStatus::Active,
        ]);

        $token = $site->issueToken();

        for ($i = 0; $i < 60; $i++) {
            $this->post('/ingest/seolead', [
                'token' => $token,
                'phone' => '+7900'.str_pad((string) $i, 7, '0', STR_PAD_LEFT),
            ])->assertSuccessful();
        }

        $this->post('/ingest/seolead', [
            'token' => $token,
            'phone' => '+79009999999',
        ])->assertStatus(429);
    }

    public function test_call_endpoint_rate_limits_by_header_token(): void
    {
        $client = AgencyClient::query()->create([
            'name' => 'Call Rate Client',
            'status' => AgencyClientStatus::Active,
        ]);

        $site = Site::query()->create([
            'agency_client_id' => $client->id,
            'name' => 'Call Rate Site',
            'domains' => ['call-rate.example.com'],
            'timezone' => 'Europe/Moscow',
            'token_hash' => '',
            'status' => SiteStatus::Active,
        ]);

        $token = $site->issueToken();

        for ($i = 0; $i < 60; $i++) {
            $this->postJson('/api/v1/leads/call', ['phone' => '+7901'.$i], [
                'X-Site-Token' => $token,
            ])->assertSuccessful();
        }

        $this->postJson('/api/v1/leads/call', ['phone' => '+79019999999'], [
            'X-Site-Token' => $token,
        ])->assertStatus(429);
    }
}
