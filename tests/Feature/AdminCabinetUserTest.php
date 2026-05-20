<?php

namespace Tests\Feature;

use App\Enums\AgencyClientStatus;
use App\Enums\SiteStatus;
use App\Enums\UserRole;
use App\Models\AgencyClient;
use App\Models\Site;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AdminCabinetUserTest extends TestCase
{
    use RefreshDatabase;

    private AgencyClient $client;

    private AgencyClient $otherClient;

    private User $admin;

    private Site $siteA;

    private Site $siteB;

    private Site $otherSite;

    protected function setUp(): void
    {
        parent::setUp();

        $this->client = AgencyClient::query()->create([
            'name' => 'Client A',
            'status' => AgencyClientStatus::Active,
        ]);

        $this->otherClient = AgencyClient::query()->create([
            'name' => 'Client B',
            'status' => AgencyClientStatus::Active,
        ]);

        $this->siteA = Site::query()->create([
            'agency_client_id' => $this->client->id,
            'name' => 'Site A',
            'domains' => ['a.test'],
            'timezone' => 'Europe/Moscow',
            'token_hash' => 'hash-a',
            'status' => SiteStatus::Active,
        ]);

        $this->siteB = Site::query()->create([
            'agency_client_id' => $this->client->id,
            'name' => 'Site B',
            'domains' => ['b.test'],
            'timezone' => 'Europe/Moscow',
            'token_hash' => 'hash-b',
            'status' => SiteStatus::Active,
        ]);

        $this->otherSite = Site::query()->create([
            'agency_client_id' => $this->otherClient->id,
            'name' => 'Other Site',
            'domains' => ['other.test'],
            'timezone' => 'Europe/Moscow',
            'token_hash' => 'hash-other',
            'status' => SiteStatus::Active,
        ]);

        $this->admin = User::query()->create([
            'name' => 'Admin',
            'email' => 'admin-cabinet@test.local',
            'password' => Hash::make('password'),
            'role' => UserRole::PlatformAdmin,
        ]);
    }

    public function test_admin_can_create_cabinet_user_with_selected_sites(): void
    {
        $response = $this->actingAs($this->admin)
            ->postJson('/api/admin/clients/'.$this->client->id.'/cabinet-users', [
                'name' => 'LK User',
                'email' => 'lk@test.local',
                'password' => 'password',
                'cabinet_all_sites' => false,
                'is_active' => true,
                'site_ids' => [$this->siteA->id],
            ])
            ->assertCreated()
            ->assertJsonStructure(['data' => ['id', 'email'], 'generated_password']);

        $userId = $response->json('data.id');

        $this->assertDatabaseHas('users', [
            'id' => $userId,
            'email' => 'lk@test.local',
            'cabinet_all_sites' => false,
        ]);

        $this->assertDatabaseHas('site_user', [
            'user_id' => $userId,
            'site_id' => $this->siteA->id,
        ]);
    }

    public function test_cannot_create_cabinet_user_without_sites_when_not_all_sites(): void
    {
        $this->actingAs($this->admin)
            ->postJson('/api/admin/clients/'.$this->client->id.'/cabinet-users', [
                'name' => 'LK User',
                'email' => 'lk2@test.local',
                'password' => 'password',
                'cabinet_all_sites' => false,
                'is_active' => true,
                'site_ids' => [],
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['site_ids']);
    }

    public function test_cannot_assign_foreign_client_site(): void
    {
        $this->actingAs($this->admin)
            ->postJson('/api/admin/clients/'.$this->client->id.'/cabinet-users', [
                'name' => 'LK User',
                'email' => 'lk3@test.local',
                'password' => 'password',
                'cabinet_all_sites' => false,
                'is_active' => true,
                'site_ids' => [$this->otherSite->id],
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['site_ids.0']);
    }

    public function test_admin_can_update_cabinet_user_to_all_sites(): void
    {
        $user = User::query()->create([
            'name' => 'LK',
            'email' => 'lk4@test.local',
            'password' => Hash::make('password'),
            'role' => UserRole::ClientUser,
            'agency_client_id' => $this->client->id,
            'cabinet_all_sites' => false,
            'is_active' => true,
        ]);
        $user->sites()->attach($this->siteA->id);

        $this->actingAs($this->admin)
            ->putJson('/api/admin/clients/'.$this->client->id.'/cabinet-users/'.$user->id, [
                'name' => 'LK Updated',
                'email' => 'lk4@test.local',
                'cabinet_all_sites' => true,
                'is_active' => true,
                'site_ids' => [],
            ])
            ->assertOk();

        $user->refresh();
        $this->assertTrue($user->cabinet_all_sites);
        $this->assertCount(0, $user->sites);
    }
}
