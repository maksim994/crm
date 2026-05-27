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

class SiteTokenPersistenceTest extends TestCase
{
    use RefreshDatabase;

    public function test_site_show_returns_persisted_token(): void
    {
        $admin = $this->createAdmin();
        $site = $this->createSite();
        $issued = $site->issueToken();

        $this->actingAs($admin)
            ->getJson('/api/admin/sites/'.$site->id)
            ->assertOk()
            ->assertJsonPath('token', $issued);
    }

    public function test_regenerate_token_updates_persisted_token(): void
    {
        $admin = $this->createAdmin();
        $site = $this->createSite();
        $site->issueToken();

        $response = $this->actingAs($admin)
            ->postJson('/api/admin/sites/'.$site->id.'/regenerate-token')
            ->assertOk();

        $newToken = $response->json('token');
        $this->assertNotNull($newToken);

        $site->refresh();
        $this->assertSame($newToken, $site->plainToken());
    }

    private function createAdmin(): User
    {
        return User::query()->create([
            'name' => 'Admin',
            'email' => 'admin@test.local',
            'password' => Hash::make('password'),
            'role' => UserRole::PlatformAdmin,
            'is_active' => true,
        ]);
    }

    private function createSite(): Site
    {
        $client = AgencyClient::query()->create([
            'name' => 'Test Client',
            'status' => AgencyClientStatus::Active,
        ]);

        return Site::query()->create([
            'agency_client_id' => $client->id,
            'name' => 'Test Site',
            'domains' => ['example.com'],
            'timezone' => 'Europe/Moscow',
            'token_hash' => '',
            'status' => SiteStatus::Active,
        ]);
    }
}
