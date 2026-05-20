<?php

namespace Tests\Feature;

use App\Enums\AgencyClientStatus;
use App\Enums\LeadChannel;
use App\Enums\LeadStatus;
use App\Enums\SiteStatus;
use App\Models\AgencyClient;
use App\Models\Lead;
use App\Models\Site;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PruneOldLeadsCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_prune_deletes_leads_older_than_retention(): void
    {
        $site = $this->createSite();

        Lead::query()->create([
            'site_id' => $site->id,
            'channel' => LeadChannel::Form,
            'phone' => '+79001111111',
            'lead_status' => LeadStatus::NotProcessed,
            'is_duplicate' => false,
            'created_at' => now()->subMonths(25),
        ]);

        Lead::query()->create([
            'site_id' => $site->id,
            'channel' => LeadChannel::Form,
            'phone' => '+79002222222',
            'lead_status' => LeadStatus::NotProcessed,
            'is_duplicate' => false,
            'created_at' => now()->subMonth(),
        ]);

        $this->artisan('leads:prune', ['--months' => 24])
            ->assertSuccessful();

        $this->assertDatabaseCount('leads', 1);
        $this->assertDatabaseHas('leads', ['phone' => '+79002222222']);
    }

    private function createSite(): Site
    {
        $client = AgencyClient::query()->create([
            'name' => 'Test',
            'status' => AgencyClientStatus::Active,
        ]);

        return Site::query()->create([
            'agency_client_id' => $client->id,
            'name' => 'Site',
            'domains' => ['example.com'],
            'timezone' => 'Europe/Moscow',
            'token_hash' => '',
            'status' => SiteStatus::Active,
        ]);
    }
}
