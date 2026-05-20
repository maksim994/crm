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

class ClientCabinetTest extends TestCase
{
    use RefreshDatabase;

    private AgencyClient $clientA;

    private AgencyClient $clientB;

    private User $clientUser;

    private Site $siteA;

    private Site $siteA2;

    protected function setUp(): void
    {
        parent::setUp();

        $this->clientA = AgencyClient::query()->create([
            'name' => 'Client A',
            'status' => AgencyClientStatus::Active,
        ]);

        $this->clientB = AgencyClient::query()->create([
            'name' => 'Client B',
            'status' => AgencyClientStatus::Active,
        ]);

        $this->siteA = Site::query()->create([
            'agency_client_id' => $this->clientA->id,
            'name' => 'Site A',
            'domains' => ['a.test'],
            'timezone' => 'Europe/Moscow',
            'token_hash' => 'hash',
            'status' => SiteStatus::Active,
        ]);

        $this->siteA2 = Site::query()->create([
            'agency_client_id' => $this->clientA->id,
            'name' => 'Site A2',
            'domains' => ['a2.test'],
            'timezone' => 'Europe/Moscow',
            'token_hash' => 'hash-a2',
            'status' => SiteStatus::Active,
        ]);

        $siteB = Site::query()->create([
            'agency_client_id' => $this->clientB->id,
            'name' => 'Site B',
            'domains' => ['b.test'],
            'timezone' => 'Europe/Moscow',
            'token_hash' => 'hash2',
            'status' => SiteStatus::Active,
        ]);

        Lead::query()->create([
            'site_id' => $this->siteA->id,
            'channel' => LeadChannel::Form,
            'phone' => '+79001112233',
            'lead_status' => LeadStatus::NotProcessed,
        ]);

        Lead::query()->create([
            'site_id' => $this->siteA2->id,
            'channel' => LeadChannel::Form,
            'phone' => '+79005556677',
            'lead_status' => LeadStatus::NotProcessed,
        ]);

        Lead::query()->create([
            'site_id' => $siteB->id,
            'channel' => LeadChannel::Form,
            'phone' => '+79009998877',
            'lead_status' => LeadStatus::NotProcessed,
        ]);

        $this->clientUser = User::query()->create([
            'name' => 'Client User',
            'email' => 'client@test.local',
            'password' => Hash::make('password'),
            'role' => UserRole::ClientUser,
            'agency_client_id' => $this->clientA->id,
            'cabinet_all_sites' => true,
            'is_active' => true,
        ]);
    }

    public function test_client_user_can_login(): void
    {
        $this->withSession([])
            ->postJson('/api/client/login', [
                'email' => 'client@test.local',
                'password' => 'password',
            ])
            ->assertOk()
            ->assertJsonPath('user.email', 'client@test.local');
    }

    public function test_platform_admin_cannot_use_client_api(): void
    {
        $admin = User::query()->create([
            'name' => 'Admin',
            'email' => 'admin@test.local',
            'password' => Hash::make('password'),
            'role' => UserRole::PlatformAdmin,
        ]);

        $this->actingAs($admin)
            ->getJson('/api/client/leads')
            ->assertForbidden();
    }

    public function test_client_user_sees_only_own_leads(): void
    {
        $response = $this->actingAs($this->clientUser)
            ->getJson('/api/client/leads')
            ->assertOk();

        $this->assertCount(2, $response->json('data'));
        $phones = collect($response->json('data'))->pluck('phone')->all();
        $this->assertContains('+79001112233', $phones);
        $this->assertContains('+79005556677', $phones);
        $this->assertArrayNotHasKey('visitor_ip', $response->json('data.0'));
        $this->assertArrayNotHasKey('manager_comment', $response->json('data.0'));
    }

    public function test_client_user_cannot_view_other_client_lead(): void
    {
        $otherLead = Lead::query()->whereHas('site', fn ($q) => $q->where('agency_client_id', $this->clientB->id))->firstOrFail();

        $this->actingAs($this->clientUser)
            ->getJson('/api/client/leads/'.$otherLead->id)
            ->assertForbidden();
    }

    public function test_foreign_site_id_filter_returns_validation_error(): void
    {
        $foreignSite = Site::query()->where('agency_client_id', $this->clientB->id)->firstOrFail();

        $this->actingAs($this->clientUser)
            ->getJson('/api/client/leads?site_id='.$foreignSite->id)
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['site_id']);
    }

    public function test_restricted_cabinet_user_sees_only_allowed_site_leads(): void
    {
        $restricted = User::query()->create([
            'name' => 'Restricted',
            'email' => 'restricted@test.local',
            'password' => Hash::make('password'),
            'role' => UserRole::ClientUser,
            'agency_client_id' => $this->clientA->id,
            'cabinet_all_sites' => false,
            'is_active' => true,
        ]);
        $restricted->sites()->attach($this->siteA->id);

        $response = $this->actingAs($restricted)
            ->getJson('/api/client/leads')
            ->assertOk();

        $this->assertCount(1, $response->json('data'));
        $this->assertSame('+79001112233', $response->json('data.0.phone'));
    }

    public function test_restricted_user_cannot_filter_by_unassigned_site_of_same_client(): void
    {
        $restricted = User::query()->create([
            'name' => 'Restricted',
            'email' => 'restricted2@test.local',
            'password' => Hash::make('password'),
            'role' => UserRole::ClientUser,
            'agency_client_id' => $this->clientA->id,
            'cabinet_all_sites' => false,
            'is_active' => true,
        ]);
        $restricted->sites()->attach($this->siteA->id);

        $this->actingAs($restricted)
            ->getJson('/api/client/leads?site_id='.$this->siteA2->id)
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['site_id']);
    }

    public function test_inactive_cabinet_user_cannot_access_api(): void
    {
        $this->clientUser->update(['is_active' => false]);

        $this->actingAs($this->clientUser)
            ->getJson('/api/client/leads')
            ->assertForbidden();
    }

    public function test_client_user_can_export_csv(): void
    {
        $response = $this->actingAs($this->clientUser)
            ->get('/api/client/leads/export');

        $response->assertOk();
        $response->assertHeader('content-type', 'text/csv; charset=UTF-8');
        $this->assertStringContainsString('Дата', $response->streamedContent());
        $content = $response->streamedContent();
        $this->assertStringContainsString('+79001112233', $content);
        $this->assertStringContainsString('+79005556677', $content);
        $this->assertStringNotContainsString('+79009998877', $content);
    }
}
