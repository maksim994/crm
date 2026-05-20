<?php

namespace Database\Seeders;

use App\Enums\AgencyClientStatus;
use App\Enums\LeadChannel;
use App\Enums\LeadStatus;
use App\Enums\SiteStatus;
use App\Enums\UserRole;
use App\Models\AgencyClient;
use App\Models\Lead;
use App\Models\Site;
use App\Models\User;
use App\Support\InboundEmailAddress;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DemoDataSeeder extends Seeder
{
    /** Plaintext токены для локальной отладки (этап 4) */
    public static ?string $ruflexToken = null;

    public static ?string $testLpToken = null;

    public function run(): void
    {
        $client = AgencyClient::query()->updateOrCreate(
            ['inn' => '7700000000'],
            [
                'name' => 'ООО «Демо Кровля»',
                'contact_name' => 'Игорь Савин',
                'contact_email' => 'demo@example.com',
                'contact_phone' => '+79000000000',
                'manager_comment' => 'Тестовый заказчик для разработки',
                'status' => AgencyClientStatus::Active,
            ],
        );

        $ruflex = Site::query()->firstOrCreate(
            ['agency_client_id' => $client->id, 'name' => 'Ruflex Pro'],
            [
                'domains' => ['ruflex-pro.ru'],
                'metrika_counter_id' => '57691633',
                'timezone' => 'Europe/Moscow',
                'token_hash' => '',
                'status' => SiteStatus::Active,
            ],
        );
        $ruflex->update(['email_inbound_address' => InboundEmailAddress::forSite($ruflex)]);
        self::$ruflexToken = $this->ensureSiteToken($ruflex);

        $testLp = Site::query()->firstOrCreate(
            ['agency_client_id' => $client->id, 'name' => 'Тест LP'],
            [
                'domains' => ['test-lp.local', 'localhost'],
                'metrika_counter_id' => null,
                'timezone' => 'Europe/Moscow',
                'token_hash' => '',
                'status' => SiteStatus::Active,
            ],
        );
        $testLp->update(['email_inbound_address' => InboundEmailAddress::forSite($testLp)]);
        self::$testLpToken = $this->ensureSiteToken($testLp);

        Lead::query()->firstOrCreate(
            [
                'site_id' => $ruflex->id,
                'phone' => '+79001112233',
            ],
            [
                'channel' => LeadChannel::Form,
                'contact_name' => 'Тестовый лид',
                'form_description' => 'Связаться с нами в футере',
                'lead_status' => LeadStatus::NotProcessed,
                'metrika_client_id' => '17791064241773632',
                'utm_source' => 'yandex-tovarnaya',
                'utm_medium' => 'cpc',
                'utm_campaign' => 'cid|707701012|search',
                'advertising_channel' => 'Переходы по рекламе',
                'landing_domain' => 'ruflex-pro.ru',
            ],
        );

        User::query()->updateOrCreate(
            ['email' => 'admin@wbooster.local'],
            [
                'name' => 'Admin',
                'password' => Hash::make('password'),
                'role' => UserRole::PlatformAdmin,
            ],
        );

        User::query()->updateOrCreate(
            ['email' => 'client@demo.example.com'],
            [
                'name' => 'Клиент демо',
                'password' => Hash::make('password'),
                'role' => UserRole::ClientUser,
                'agency_client_id' => $client->id,
            ],
        );

        $this->command?->info('Demo agency client: '.$client->name);
        $this->command?->info('Ruflex Pro token: '.self::$ruflexToken);
        $this->command?->info('Test LP token: '.self::$testLpToken);
        $this->command?->info('Admin: admin@wbooster.local / password');
        $this->command?->info('Client LK: client@demo.example.com / password');
    }

    private function ensureSiteToken(Site $site): string
    {
        if (filled($site->token_hash)) {
            return '(уже выдан — смотрите в админке или перевыпустите)';
        }

        return $site->issueToken();
    }
}
