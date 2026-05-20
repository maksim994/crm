<?php

namespace Tests\Feature;

use App\Enums\AgencyClientStatus;
use App\Enums\LeadChannel;
use App\Enums\SiteStatus;
use App\Models\AgencyClient;
use App\Models\Lead;
use App\Models\Site;
use App\Support\InboundEmailAddress;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InboundEmailWebhookTest extends TestCase
{
    use RefreshDatabase;

    public function test_inbound_email_webhook_creates_lead_synchronously(): void
    {
        config(['crm.inbound_webhook_secret' => null]);

        $site = $this->createSite();
        $to = InboundEmailAddress::forSite($site);
        $site->update(['email_inbound_address' => $to]);

        $response = $this->postJson('/ingest/inbound-email', [
            'to' => $to,
            'from' => 'client@example.com',
            'subject' => 'Заявка с почты',
            'body' => 'Позвоните +79005554433',
        ]);

        $response->assertCreated()
            ->assertJsonPath('channel', 'email');

        $this->assertDatabaseHas('leads', [
            'site_id' => $site->id,
            'channel' => LeadChannel::Email->value,
            'phone' => '+79005554433',
            'email' => 'client@example.com',
        ]);
    }

    public function test_mailgun_style_payload_is_accepted(): void
    {
        config(['crm.inbound_webhook_secret' => null]);

        $site = $this->createSite();
        $to = InboundEmailAddress::forSite($site);
        $site->update(['email_inbound_address' => $to]);

        $this->post('/ingest/inbound-email', [
            'recipient' => $to,
            'sender' => 'ivan@mail.ru',
            'subject' => 'Вопрос',
            'body-plain' => 'Тел +79001112233',
        ])->assertCreated();

        $this->assertDatabaseHas('leads', [
            'site_id' => $site->id,
            'phone' => '+79001112233',
        ]);
    }

    public function test_webhook_requires_secret_when_configured(): void
    {
        config(['crm.inbound_webhook_secret' => 'test-secret']);

        $site = $this->createSite();
        $to = InboundEmailAddress::forSite($site);

        $this->postJson('/ingest/inbound-email', [
            'to' => $to,
            'from' => 'a@b.ru',
            'body' => '+79001112233',
        ])->assertUnauthorized();

        $this->postJson('/ingest/inbound-email', [
            'to' => $to,
            'from' => 'a@b.ru',
            'body' => '+79001112233',
        ], [
            'X-Inbound-Webhook-Secret' => 'test-secret',
        ])->assertCreated();
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
