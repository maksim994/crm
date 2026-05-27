<?php

namespace Tests\Feature;

use App\Enums\AgencyClientStatus;
use App\Enums\LeadChannel;
use App\Enums\SiteStatus;
use App\Enums\UserRole;
use App\Models\AgencyClient;
use App\Models\Lead;
use App\Models\Site;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class ManualLeadCreationTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_create_lead_manually(): void
    {
        $admin = $this->createAdmin();
        $site = $this->createSite();

        $response = $this->actingAs($admin, 'sanctum')->postJson('/api/admin/leads', [
            'site_id' => $site->id,
            'phone' => '+79001112233',
            'contact_name' => 'Иван',
            'comment' => 'Звонил сам',
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.channel', LeadChannel::Manual->value)
            ->assertJsonPath('data.phone', '+79001112233')
            ->assertJsonPath('data.contact_name', 'Иван');

        $this->assertDatabaseHas('leads', [
            'site_id' => $site->id,
            'channel' => LeadChannel::Manual->value,
            'phone' => '+79001112233',
        ]);
    }

    public function test_manual_lead_requires_phone_or_email(): void
    {
        $admin = $this->createAdmin();
        $site = $this->createSite();

        $this->actingAs($admin, 'sanctum')->postJson('/api/admin/leads', [
            'site_id' => $site->id,
        ])->assertUnprocessable();
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
            'token_hash' => 'hash',
            'status' => SiteStatus::Active,
        ]);
    }
}
