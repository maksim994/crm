<?php

namespace Tests\Feature;

use App\Enums\AgencyClientStatus;
use App\Enums\SiteStatus;
use App\Models\AgencyClient;
use App\Models\Site;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EmbedConfigTest extends TestCase
{
    use RefreshDatabase;

    public function test_returns_project_emails_by_token(): void
    {
        $site = $this->createSite([
            'email_inbound_address' => 'main@client.ru',
            'email_inbound_seo' => 'seo@client.ru',
            'email_inbound_other' => 'other@client.ru',
        ]);
        $token = $site->issueToken();

        $this->getJson('/embed/config?token='.urlencode($token))
            ->assertOk()
            ->assertJsonPath('emails.ads', 'main@client.ru')
            ->assertJsonPath('emails.seo', 'seo@client.ru')
            ->assertJsonPath('emails.other', 'other@client.ru')
            ->assertHeader('Access-Control-Allow-Origin', '*');
    }

    public function test_rejects_invalid_token(): void
    {
        $this->getJson('/embed/config?token=invalid')
            ->assertUnauthorized();
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function createSite(array $overrides = []): Site
    {
        $client = AgencyClient::query()->create([
            'name' => 'Test Client',
            'status' => AgencyClientStatus::Active,
        ]);

        return Site::query()->create(array_merge([
            'agency_client_id' => $client->id,
            'name' => 'Test Site',
            'domains' => ['example.com'],
            'timezone' => 'Europe/Moscow',
            'token_hash' => '',
            'status' => SiteStatus::Active,
        ], $overrides));
    }
}
