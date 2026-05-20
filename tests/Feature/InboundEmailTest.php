<?php

namespace Tests\Feature;

use App\Enums\AgencyClientStatus;
use App\Enums\LeadChannel;
use App\Enums\SiteStatus;
use App\Jobs\ProcessInboundEmailJob;
use App\Models\AgencyClient;
use App\Models\Lead;
use App\Models\Site;
use App\Support\InboundEmailAddress;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InboundEmailTest extends TestCase
{
    use RefreshDatabase;

    public function test_inbound_email_job_creates_lead(): void
    {
        $client = AgencyClient::query()->create([
            'name' => 'Test Client',
            'status' => AgencyClientStatus::Active,
        ]);

        $site = Site::query()->create([
            'agency_client_id' => $client->id,
            'name' => 'Test Site',
            'domains' => ['example.com'],
            'timezone' => 'Europe/Moscow',
            'token_hash' => 'hash',
            'status' => SiteStatus::Active,
            'email_inbound_address' => null,
        ]);

        $to = InboundEmailAddress::forSite($site);
        $site->update(['email_inbound_address' => $to]);

        $job = new ProcessInboundEmailJob(
            to: $to,
            from: 'client@example.com',
            subject: 'Заявка с сайта',
            body: 'Позвоните мне +79005554433',
        );

        $job->handle(app(\App\Services\LeadIngestionService::class));

        $this->assertDatabaseHas('leads', [
            'site_id' => $site->id,
            'channel' => LeadChannel::Email->value,
            'phone' => '+79005554433',
            'email' => 'client@example.com',
        ]);
    }

    public function test_resolves_site_from_plus_address_local_part(): void
    {
        $siteId = 'a1b2c3d4-e5f6-7890-abcd-ef1234567890';
        $siteKey = str_replace('-', '', $siteId);
        $recipient = 'leads+'.$siteKey.'@inbound.local';

        $resolved = InboundEmailAddress::resolveSiteIdFromRecipient($recipient);

        $this->assertSame($siteId, $resolved);
    }
}
