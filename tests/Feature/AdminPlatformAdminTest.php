<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AdminPlatformAdminTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::query()->create([
            'name' => 'Admin',
            'email' => 'admin@test.local',
            'password' => Hash::make('password'),
            'role' => UserRole::PlatformAdmin,
            'is_active' => true,
        ]);
    }

    public function test_admin_can_list_platform_admins(): void
    {
        User::query()->create([
            'name' => 'Second Admin',
            'email' => 'admin2@test.local',
            'password' => Hash::make('password'),
            'role' => UserRole::PlatformAdmin,
            'is_active' => true,
        ]);

        $this->actingAs($this->admin)
            ->getJson('/api/admin/platform-admins')
            ->assertOk()
            ->assertJsonCount(2, 'data');
    }

    public function test_admin_can_create_platform_admin(): void
    {
        $this->actingAs($this->admin)
            ->postJson('/api/admin/platform-admins', [
                'name' => 'New Admin',
                'email' => 'new-admin@test.local',
                'password' => 'password',
                'is_active' => true,
            ])
            ->assertCreated()
            ->assertJsonStructure(['data' => ['id', 'email'], 'generated_password']);

        $this->assertDatabaseHas('users', [
            'email' => 'new-admin@test.local',
            'role' => UserRole::PlatformAdmin->value,
            'is_active' => true,
        ]);
    }

    public function test_admin_can_update_platform_admin(): void
    {
        $otherAdmin = User::query()->create([
            'name' => 'Other Admin',
            'email' => 'other-admin@test.local',
            'password' => Hash::make('password'),
            'role' => UserRole::PlatformAdmin,
            'is_active' => true,
        ]);

        $this->actingAs($this->admin)
            ->putJson('/api/admin/platform-admins/'.$otherAdmin->id, [
                'name' => 'Updated Admin',
                'email' => 'other-admin@test.local',
                'is_active' => false,
            ])
            ->assertOk()
            ->assertJsonPath('data.name', 'Updated Admin')
            ->assertJsonPath('data.is_active', false);
    }

    public function test_cannot_deactivate_last_active_platform_admin(): void
    {
        $this->actingAs($this->admin)
            ->putJson('/api/admin/platform-admins/'.$this->admin->id, [
                'name' => 'Admin',
                'email' => 'admin@test.local',
                'is_active' => false,
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['is_active']);
    }

    public function test_admin_can_delete_platform_admin_when_another_active_exists(): void
    {
        $secondAdmin = User::query()->create([
            'name' => 'Second Admin',
            'email' => 'second-admin@test.local',
            'password' => Hash::make('password'),
            'role' => UserRole::PlatformAdmin,
            'is_active' => true,
        ]);

        $this->actingAs($this->admin)
            ->deleteJson('/api/admin/platform-admins/'.$secondAdmin->id)
            ->assertOk();

        $this->assertDatabaseMissing('users', ['id' => $secondAdmin->id]);
    }

    public function test_admin_cannot_delete_self(): void
    {
        $this->actingAs($this->admin)
            ->deleteJson('/api/admin/platform-admins/'.$this->admin->id)
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['admin']);
    }

    public function test_admin_can_delete_inactive_platform_admin(): void
    {
        $inactiveAdmin = User::query()->create([
            'name' => 'Inactive Admin',
            'email' => 'inactive-admin@test.local',
            'password' => Hash::make('password'),
            'role' => UserRole::PlatformAdmin,
            'is_active' => false,
        ]);

        $this->actingAs($this->admin)
            ->deleteJson('/api/admin/platform-admins/'.$inactiveAdmin->id)
            ->assertOk();

        $this->assertDatabaseMissing('users', ['id' => $inactiveAdmin->id]);
    }

    public function test_inactive_platform_admin_cannot_login(): void
    {
        User::query()->create([
            'name' => 'Inactive Admin',
            'email' => 'inactive-login@test.local',
            'password' => Hash::make('password'),
            'role' => UserRole::PlatformAdmin,
            'is_active' => false,
        ]);

        $this->withSession([])
            ->postJson('/api/admin/login', [
                'email' => 'inactive-login@test.local',
                'password' => 'password',
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['email']);
    }

    public function test_inactive_platform_admin_cannot_access_admin_api(): void
    {
        $inactiveAdmin = User::query()->create([
            'name' => 'Inactive Admin',
            'email' => 'inactive-api@test.local',
            'password' => Hash::make('password'),
            'role' => UserRole::PlatformAdmin,
            'is_active' => false,
        ]);

        $this->actingAs($inactiveAdmin)
            ->getJson('/api/admin/dashboard')
            ->assertForbidden();
    }
}
