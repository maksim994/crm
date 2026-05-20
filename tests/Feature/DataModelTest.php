<?php

namespace Tests\Feature;

use App\Models\AgencyClient;
use App\Models\Lead;
use App\Models\Site;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DataModelTest extends TestCase
{
    use RefreshDatabase;

    public function test_agency_client_has_sites_and_leads(): void
    {
        $this->seed();

        $site = Site::query()->first();
        $this->assertNotNull($site);

        $this->assertGreaterThan(0, $site->leads()->count());
        $this->assertInstanceOf(Lead::class, $site->leads()->first());

        $client = AgencyClient::query()->first();
        $this->assertSame(2, $client->sites()->count());
    }

    public function test_site_token_verification(): void
    {
        $client = AgencyClient::query()->create([
            'name' => 'Test Co',
            'status' => 'active',
        ]);

        $site = Site::query()->create([
            'agency_client_id' => $client->id,
            'name' => 'Site',
            'domains' => ['example.com'],
            'token_hash' => '',
            'status' => 'active',
        ]);

        $token = $site->issueToken();

        $this->assertNotNull(Site::findByToken($token));
        $this->assertNull(Site::findByToken('invalid-token'));
        $this->assertNull(Site::findByToken($site->id.':wrong-secret'));
    }
}
