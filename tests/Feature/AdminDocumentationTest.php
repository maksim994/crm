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
            ->getJson('/api/admin/docs/rabota-s-lidami')
            ->assertOk()
            ->assertJsonStructure([
                'data' => ['slug', 'title', 'content'],
            ])
            ->assertJsonPath('data.slug', 'rabota-s-lidami');
    }

    public function test_documentation_rewrites_internal_links(): void
    {
        $response = $this->actingAs($this->admin)
            ->getJson('/api/admin/docs/chastye-problemy')
            ->assertOk();

        $content = $response->json('data.content');
        $this->assertIsString($content);
        $this->assertStringContainsString('](/docs/', $content);
    }

    public function test_admin_can_view_getting_started_documentation(): void
    {
        $this->actingAs($this->admin)
            ->getJson('/api/admin/docs/nachalo-raboty')
            ->assertOk()
            ->assertJsonPath('data.slug', 'nachalo-raboty')
            ->assertJsonFragment(['title' => 'С чего начать']);
    }

    public function test_admin_can_view_lead_fields_documentation(): void
    {
        $response = $this->actingAs($this->admin)
            ->getJson('/api/admin/docs/polya-lida')
            ->assertOk()
            ->assertJsonPath('data.slug', 'polya-lida');

        $content = $response->json('data.content');
        $this->assertIsString($content);
        $this->assertStringContainsString('/ingest/seolead', $content);
    }

    public function test_admin_can_view_form_integration_documentation(): void
    {
        $this->actingAs($this->admin)
            ->getJson('/api/admin/docs/integraciya-form')
            ->assertOk()
            ->assertJsonPath('data.slug', 'integraciya-form')
            ->assertJsonFragment(['title' => 'Интеграция форм']);
    }

    public function test_admin_can_view_email_integration_documentation(): void
    {
        $response = $this->actingAs($this->admin)
            ->getJson('/api/admin/docs/integraciya-pochty')
            ->assertOk()
            ->assertJsonPath('data.slug', 'integraciya-pochty');

        $content = $response->json('data.content');
        $this->assertIsString($content);
        $this->assertStringContainsString('mail@crm-lead.ru', $content);
    }

    public function test_admin_can_view_metrika_documentation(): void
    {
        $this->actingAs($this->admin)
            ->getJson('/api/admin/docs/metrika-dlya-menedzhera')
            ->assertOk()
            ->assertJsonPath('data.slug', 'metrika-dlya-menedzhera')
            ->assertJsonFragment(['title' => 'Яндекс.Метрика']);
    }

    public function test_documentation_reads_from_admin_copy(): void
    {
        $this->assertSame(
            base_path('docs/admin'),
            config('documentation.root'),
        );

        $this->assertFileExists(base_path('docs/admin/rabota-s-lidami.md'));
        $this->assertFileExists(base_path('docs/admin/integraciya-s-saytom.md'));
        $this->assertFileExists(base_path('docs/integraciya-s-saytom.md'));
        $this->assertNotSame(
            file_get_contents(base_path('docs/admin/integraciya-s-saytom.md')),
            file_get_contents(base_path('docs/integraciya-s-saytom.md')),
        );
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
