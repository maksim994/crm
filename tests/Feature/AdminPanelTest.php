<?php

namespace Tests\Feature;

use App\Enums\AgencyClientStatus;
use App\Enums\LeadChannel;
use App\Enums\LeadStatus;
use App\Enums\SiteStatus;
use App\Enums\UserRole;
use App\Models\AgencyClient;
use App\Models\Lead;
use App\Models\Site;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AdminPanelTest extends TestCase
{
    use RefreshDatabase;

    public function test_platform_admin_can_login(): void
    {
        User::query()->create([
            'name' => 'Admin',
            'email' => 'admin@test.local',
            'password' => Hash::make('password'),
            'role' => UserRole::PlatformAdmin,
        ]);

        $this->withSession([])
            ->postJson('/api/admin/login', [
                'email' => 'admin@test.local',
                'password' => 'password',
            ])
            ->assertOk()
            ->assertJsonStructure(['user' => ['id', 'name', 'email']]);
    }

    public function test_platform_admin_can_access_dashboard_api(): void
    {
        $admin = User::query()->create([
            'name' => 'Admin',
            'email' => 'admin2@test.local',
            'password' => Hash::make('password'),
            'role' => UserRole::PlatformAdmin,
        ]);

        $this->actingAs($admin)
            ->getJson('/api/admin/dashboard')
            ->assertOk()
            ->assertJsonStructure(['clients_count', 'sites_count', 'leads_count']);
    }

    public function test_platform_admin_can_list_sites(): void
    {
        $admin = User::query()->create([
            'name' => 'Admin',
            'email' => 'admin2@test.local',
            'password' => Hash::make('password'),
            'role' => UserRole::PlatformAdmin,
        ]);

        $this->actingAs($admin)
            ->getJson('/api/admin/sites')
            ->assertOk()
            ->assertJsonStructure(['data']);
    }

    public function test_platform_admin_can_show_client_with_sites(): void
    {
        $admin = User::query()->create([
            'name' => 'Admin',
            'email' => 'admin5@test.local',
            'password' => Hash::make('password'),
            'role' => UserRole::PlatformAdmin,
        ]);

        $client = AgencyClient::query()->create([
            'name' => 'Client Show',
            'status' => AgencyClientStatus::Active,
        ]);

        Site::query()->create([
            'agency_client_id' => $client->id,
            'name' => 'Site',
            'domains' => ['show.test'],
            'timezone' => 'Europe/Moscow',
            'token_hash' => 'hash',
            'status' => SiteStatus::Active,
        ]);

        $this->actingAs($admin)
            ->getJson('/api/admin/clients/'.$client->id)
            ->assertOk()
            ->assertJsonStructure([
                'data' => ['id', 'name', 'sites_count', 'leads_count'],
                'sites' => [['id', 'name']],
                'users',
            ]);
    }

    public function test_platform_admin_can_show_site_with_integration(): void
    {
        $admin = User::query()->create([
            'name' => 'Admin',
            'email' => 'admin4@test.local',
            'password' => Hash::make('password'),
            'role' => UserRole::PlatformAdmin,
        ]);

        $client = AgencyClient::query()->create([
            'name' => 'Client',
            'status' => AgencyClientStatus::Active,
        ]);

        $site = Site::query()->create([
            'agency_client_id' => $client->id,
            'name' => 'Site',
            'domains' => ['example.test'],
            'timezone' => 'Europe/Moscow',
            'token_hash' => 'hash',
            'status' => SiteStatus::Active,
        ]);

        $this->actingAs($admin)
            ->getJson('/api/admin/sites/'.$site->id)
            ->assertOk()
            ->assertJsonStructure(['data' => ['id', 'name', 'domains'], 'integration', 'ingest_url']);
    }

    public function test_client_user_cannot_access_admin_api(): void
    {
        $clientUser = User::query()->create([
            'name' => 'Client',
            'email' => 'client@test.local',
            'password' => Hash::make('password'),
            'role' => UserRole::ClientUser,
        ]);

        $this->actingAs($clientUser)
            ->getJson('/api/admin/dashboard')
            ->assertForbidden();
    }

    public function test_guest_cannot_access_admin_api(): void
    {
        $this->getJson('/api/admin/dashboard')
            ->assertUnauthorized();
    }

    public function test_admin_can_filter_leads_by_agency_client(): void
    {
        $admin = User::query()->create([
            'name' => 'Admin',
            'email' => 'admin3@test.local',
            'password' => Hash::make('password'),
            'role' => UserRole::PlatformAdmin,
        ]);

        $clientA = AgencyClient::query()->create([
            'name' => 'Client A',
            'status' => AgencyClientStatus::Active,
        ]);

        $clientB = AgencyClient::query()->create([
            'name' => 'Client B',
            'status' => AgencyClientStatus::Active,
        ]);

        $siteA = Site::query()->create([
            'agency_client_id' => $clientA->id,
            'name' => 'Site A',
            'domains' => ['a.test'],
            'timezone' => 'Europe/Moscow',
            'token_hash' => 'hash',
            'status' => SiteStatus::Active,
        ]);

        $siteB = Site::query()->create([
            'agency_client_id' => $clientB->id,
            'name' => 'Site B',
            'domains' => ['b.test'],
            'timezone' => 'Europe/Moscow',
            'token_hash' => 'hash2',
            'status' => SiteStatus::Active,
        ]);

        Lead::query()->create([
            'site_id' => $siteA->id,
            'channel' => LeadChannel::Form,
            'phone' => '+79001112233',
            'lead_status' => LeadStatus::NotProcessed,
        ]);

        Lead::query()->create([
            'site_id' => $siteB->id,
            'channel' => LeadChannel::Form,
            'phone' => '+79009998877',
            'lead_status' => LeadStatus::NotProcessed,
        ]);

        $response = $this->actingAs($admin)
            ->getJson('/api/admin/leads?agency_client_id='.$clientA->id)
            ->assertOk();

        $this->assertCount(1, $response->json('data'));
        $this->assertSame('+79001112233', $response->json('data.0.phone'));
    }
}
