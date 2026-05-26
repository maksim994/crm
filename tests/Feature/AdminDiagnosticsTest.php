<?php

namespace Tests\Feature;

use App\Enums\AgencyClientStatus;
use App\Enums\SiteStatus;
use App\Enums\UserRole;
use App\Models\AgencyClient;
use App\Models\Site;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class AdminDiagnosticsTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private Site $site;

    protected function setUp(): void
    {
        parent::setUp();

        Config::set('metrika.reporting_enabled', true);
        Config::set('metrika.oauth_token', 'test-oauth-token');
        Config::set('crm.inbound_imap.enabled', false);

        $this->admin = User::query()->create([
            'name' => 'Platform Admin',
            'email' => 'admin-diagnostics@test.local',
            'password' => bcrypt('password'),
            'role' => UserRole::PlatformAdmin,
            'is_active' => true,
        ]);

        $client = AgencyClient::query()->create([
            'name' => 'Client',
            'status' => AgencyClientStatus::Active,
        ]);

        $this->site = Site::query()->create([
            'agency_client_id' => $client->id,
            'name' => 'Site A',
            'domains' => ['a.test'],
            'timezone' => 'Europe/Moscow',
            'token_hash' => hash('sha256', 'token'),
            'status' => SiteStatus::Active,
            'metrika_counter_id' => '57691633',
            'metrika_brand_keywords' => ['brand'],
            'email_inbound_address' => 'leads@a.test',
        ]);
    }

    public function test_platform_diagnostics_requires_admin(): void
    {
        $this->getJson('/api/admin/diagnostics')->assertUnauthorized();
    }

    public function test_platform_diagnostics_returns_groups(): void
    {
        Http::fake([
            'api-metrika.yandex.net/*' => Http::response([
                'total_rows' => 0,
                'data' => [],
            ]),
        ]);

        $response = $this->actingAs($this->admin)->getJson('/api/admin/diagnostics');

        $response->assertOk()
            ->assertJsonStructure([
                'status',
                'checked_at',
                'groups' => [
                    ['id', 'title', 'checks' => [['id', 'label', 'status', 'message']]],
                ],
            ])
            ->assertJsonPath('groups.0.id', 'infrastructure')
            ->assertJsonPath('groups.1.id', 'metrika');
    }

    public function test_site_diagnostics_returns_project_checks(): void
    {
        Http::fake([
            'api-metrika.yandex.net/*' => Http::response([
                'total_rows' => 1,
                'data' => [
                    [
                        'dimensions' => [['id' => 'organic', 'name' => 'Organic']],
                        'metrics' => [10],
                    ],
                ],
            ]),
        ]);

        $this->actingAs($this->admin)
            ->getJson('/api/admin/sites/'.$this->site->id.'/diagnostics')
            ->assertOk()
            ->assertJsonPath('site.id', $this->site->id)
            ->assertJsonPath('groups.0.id', 'project')
            ->assertJsonPath('groups.1.id', 'metrika');
    }
}
