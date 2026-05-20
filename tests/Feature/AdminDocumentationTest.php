<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AdminDocumentationTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::query()->create([
            'name' => 'Admin',
            'email' => 'admin-docs@test.local',
            'password' => Hash::make('password'),
            'role' => UserRole::PlatformAdmin,
            'is_active' => true,
        ]);
    }

    public function test_admin_can_list_documentation(): void
    {
        $this->actingAs($this->admin)
            ->getJson('/api/admin/docs')
            ->assertOk()
            ->assertJsonStructure([
                'groups' => [
                    ['title', 'documents' => [['slug', 'title', 'description']]],
                ],
            ]);
    }

    public function test_admin_can_view_documentation_page(): void
    {
        $this->actingAs($this->admin)
            ->getJson('/api/admin/docs/integraciya-s-saytom')
            ->assertOk()
            ->assertJsonStructure([
                'data' => ['slug', 'title', 'content'],
            ])
            ->assertJsonPath('data.slug', 'integraciya-s-saytom');
    }

    public function test_documentation_rewrites_internal_links(): void
    {
        $response = $this->actingAs($this->admin)
            ->getJson('/api/admin/docs/etapy')
            ->assertOk();

        $content = $response->json('data.content');
        $this->assertIsString($content);
        $this->assertStringContainsString('](/docs/', $content);
    }

    public function test_unknown_documentation_slug_returns_not_found(): void
    {
        $this->actingAs($this->admin)
            ->getJson('/api/admin/docs/not-existing-doc')
            ->assertNotFound();
    }

    public function test_guest_cannot_access_documentation(): void
    {
        $this->getJson('/api/admin/docs')
            ->assertUnauthorized();
    }
}
