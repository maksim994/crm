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

class ClientAnalyticsTest extends TestCase
{
    use RefreshDatabase;

    private User $clientUser;

    private Site $site;

    protected function setUp(): void
    {
        parent::setUp();

        Config::set('metrika.reporting_enabled', true);
        Config::set('metrika.oauth_token', 'test-oauth-token');

        $client = AgencyClient::query()->create([
            'name' => 'Client A',
            'status' => AgencyClientStatus::Active,
        ]);

        $this->site = Site::query()->create([
            'agency_client_id' => $client->id,
            'name' => 'Site A',
            'domains' => ['a.test'],
            'timezone' => 'Europe/Moscow',
            'token_hash' => 'hash',
            'status' => SiteStatus::Active,
            'metrika_counter_id' => '57691633',
            'metrika_brand_keywords' => ['brand'],
        ]);

        $this->clientUser = User::query()->create([
            'name' => 'Client User',
            'email' => 'client-analytics@test.local',
            'password' => bcrypt('password'),
            'role' => UserRole::ClientUser,
            'agency_client_id' => $client->id,
            'cabinet_all_sites' => true,
            'is_active' => true,
        ]);
    }

    public function test_traffic_sources_returns_breakdown(): void
    {
        Http::fake([
            'api-metrika.yandex.net/*' => Http::response([
                'total_rows' => 2,
                'data' => [
                    [
                        'dimensions' => [
                            ['id' => 'ad', 'name' => 'Переходы по рекламе'],
                        ],
                        'metrics' => [1087, 25.0, 2.08, 165],
                    ],
                    [
                        'dimensions' => [
                            ['id' => 'organic', 'name' => 'Переходы из поисковых систем'],
                        ],
                        'metrics' => [1350, 19.0, 2.06, 246],
                    ],
                ],
            ]),
        ]);

        $response = $this->actingAs($this->clientUser)
            ->getJson('/api/client/projects/'.$this->site->id.'/analytics/traffic-sources?'.http_build_query([
                'date_from' => '2026-04-01',
                'date_to' => '2026-04-30',
            ]));

        $response->assertOk()
            ->assertJsonPath('data.summary.labels.0', 'Переходы по рекламе')
            ->assertJsonPath('data.table.1.visits', 1350)
            ->assertJsonPath('data.table.1.avg_duration_sec', 246);
    }

    public function test_analytics_without_counter_returns_503(): void
    {
        $this->site->update(['metrika_counter_id' => null]);

        $this->actingAs($this->clientUser)
            ->getJson('/api/client/projects/'.$this->site->id.'/analytics/devices?'.http_build_query([
                'date_from' => '2026-04-01',
                'date_to' => '2026-04-30',
            ]))
            ->assertStatus(503)
            ->assertJsonPath('message', 'У проекта не указан счётчик Яндекс Метрики.');
    }

    public function test_foreign_site_analytics_is_forbidden(): void
    {
        $otherClient = AgencyClient::query()->create([
            'name' => 'Client B',
            'status' => AgencyClientStatus::Active,
        ]);

        $foreignSite = Site::query()->create([
            'agency_client_id' => $otherClient->id,
            'name' => 'Site B',
            'domains' => ['b.test'],
            'timezone' => 'Europe/Moscow',
            'token_hash' => 'hash-b',
            'status' => SiteStatus::Active,
            'metrika_counter_id' => '12345',
        ]);

        $this->actingAs($this->clientUser)
            ->getJson('/api/client/projects/'.$foreignSite->id.'/analytics/geography?'.http_build_query([
                'date_from' => '2026-04-01',
                'date_to' => '2026-04-30',
            ]))
            ->assertForbidden();
    }

    public function test_metrika_access_denied_returns_503(): void
    {
        Http::fake([
            'api-metrika.yandex.net/*' => Http::response([
                'errors' => [
                    ['error_type' => 'access_denied', 'message' => 'Access is denied'],
                ],
                'code' => 403,
                'message' => 'Access is denied',
            ], 403),
        ]);

        $this->actingAs($this->clientUser)
            ->getJson('/api/client/projects/'.$this->site->id.'/analytics/devices?'.http_build_query([
                'date_from' => '2026-04-01',
                'date_to' => '2026-04-30',
            ]))
            ->assertStatus(503)
            ->assertJsonPath('message', 'Нет доступа к счётчику Метрики 57691633. Проверьте OAuth-токен (право metrika:read) и доступ к счётчику.');
    }

    public function test_branded_search_without_keywords_returns_503(): void
    {
        $this->site->update(['metrika_brand_keywords' => null]);

        $this->actingAs($this->clientUser)
            ->getJson('/api/client/projects/'.$this->site->id.'/analytics/search-branded?'.http_build_query([
                'date_from' => '2026-04-01',
                'date_to' => '2026-04-30',
            ]))
            ->assertStatus(503)
            ->assertJsonPath('message', 'Укажите ключевые слова бренда в настройках проекта (админка → проект → «Ключевые слова бренда»).');
    }

    public function test_branded_search_uses_search_phrase_filter(): void
    {
        Http::fake([
            'api-metrika.yandex.net/*' => function ($request) {
                $filters = $request->data()['filters'] ?? '';

                $this->assertStringContainsString("ym:s:searchPhrase=~'", (string) $filters);
                $this->assertStringContainsString("ym:s:trafficSource=='organic'", (string) $filters);

                return Http::response([
                    'total_rows' => 1,
                    'data' => [
                        [
                            'dimensions' => [
                                ['id' => 'yandex', 'name' => 'Яндекс'],
                            ],
                            'metrics' => [120],
                        ],
                    ],
                ]);
            },
        ]);

        $this->actingAs($this->clientUser)
            ->getJson('/api/client/projects/'.$this->site->id.'/analytics/search-branded?'.http_build_query([
                'date_from' => '2026-04-01',
                'date_to' => '2026-04-30',
            ]))
            ->assertOk()
            ->assertJsonPath('data.summary.labels.0', 'Яндекс');
    }
}
