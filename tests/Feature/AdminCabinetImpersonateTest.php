<?php

namespace Tests\Feature;

use App\Enums\AgencyClientStatus;
use App\Enums\UserRole;
use App\Models\AgencyClient;
use App\Models\User;
use App\Support\CabinetImpersonation;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AdminCabinetImpersonateTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_issue_impersonation_link(): void
    {
        $client = AgencyClient::query()->create([
            'name' => 'Client',
            'status' => AgencyClientStatus::Active,
        ]);

        $cabinetUser = User::query()->create([
            'name' => 'LK',
            'email' => 'lk@test.local',
            'password' => Hash::make('password'),
            'role' => UserRole::ClientUser,
            'agency_client_id' => $client->id,
            'cabinet_all_sites' => true,
            'is_active' => true,
        ]);

        $admin = User::query()->create([
            'name' => 'Admin',
            'email' => 'admin-imp@test.local',
            'password' => Hash::make('password'),
            'role' => UserRole::PlatformAdmin,
        ]);

        $this->actingAs($admin)
            ->postJson('/api/admin/clients/'.$client->id.'/impersonate', [
                'user_id' => $cabinetUser->id,
            ])
            ->assertOk()
            ->assertJsonStructure(['token', 'cabinet_path', 'cabinet_user'])
            ->assertJsonPath('cabinet_path', fn (string $path) => str_starts_with($path, '/cabinet/?impersonate='))
            ->assertJsonPath('cabinet_user.email', 'lk@test.local');
    }

    public function test_client_can_consume_impersonation_token(): void
    {
        $client = AgencyClient::query()->create([
            'name' => 'Client',
            'status' => AgencyClientStatus::Active,
        ]);

        $cabinetUser = User::query()->create([
            'name' => 'LK',
            'email' => 'lk2@test.local',
            'password' => Hash::make('password'),
            'role' => UserRole::ClientUser,
            'agency_client_id' => $client->id,
            'cabinet_all_sites' => true,
            'is_active' => true,
        ]);

        $token = CabinetImpersonation::issue($client, $cabinetUser);

        $this->postJson('/api/client/impersonate', ['token' => $token])
            ->assertOk()
            ->assertJsonPath('user.email', 'lk2@test.local');

        $this->assertAuthenticatedAs($cabinetUser);

        $this->postJson('/api/client/impersonate', ['token' => $token])
            ->assertUnprocessable();
    }
}
