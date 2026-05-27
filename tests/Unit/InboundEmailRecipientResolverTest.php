<?php

namespace Tests\Unit;

use App\Enums\AgencyClientStatus;
use App\Enums\SiteStatus;
use App\Models\AgencyClient;
use App\Models\Site;
use App\Support\InboundEmailRecipientResolver;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InboundEmailRecipientResolverTest extends TestCase
{
    use RefreshDatabase;

    public function test_extracts_addresses_from_forward_headers(): void
    {
        $resolver = new InboundEmailRecipientResolver;

        $headers = implode("\r\n", [
            'Delivered-To: mail@mv-deploy.ru',
            'X-Original-To: zayavki@client.ru',
            'To: mail@mv-deploy.ru',
        ]);

        $addresses = $resolver->extractAddresses($headers, '');

        $this->assertContains('zayavki@client.ru', $addresses);
        $this->assertContains('mail@mv-deploy.ru', $addresses);
    }

    public function test_extracts_to_from_forwarded_body(): void
    {
        $resolver = new InboundEmailRecipientResolver;

        $body = "---------- Пересланное сообщение ----------\nКому: sales@example.org\n";

        $addresses = $resolver->extractAddresses('', $body);

        $this->assertContains('sales@example.org', $addresses);
    }

    public function test_resolves_site_by_inbound_address_case_insensitive(): void
    {
        $site = $this->createSite(['email_inbound_address' => 'Zayavki@Client.RU']);

        $resolver = new InboundEmailRecipientResolver;

        $resolved = $resolver->resolveSite(['zayavki@client.ru', 'mail@mv-deploy.ru']);

        $this->assertNotNull($resolved);
        $this->assertTrue($resolved->is($site));
    }

    public function test_resolve_returns_null_when_no_match(): void
    {
        $this->createSite(['email_inbound_address' => 'other@client.ru']);

        $resolver = new InboundEmailRecipientResolver;

        $this->assertNull($resolver->resolveSite(['unknown@client.ru']));
    }

    public function test_resolves_site_by_seo_inbound_address(): void
    {
        $site = $this->createSite(['email_inbound_seo' => 'seo@client.ru']);

        $resolver = new InboundEmailRecipientResolver;

        $resolved = $resolver->resolveSite(['seo@client.ru']);

        $this->assertNotNull($resolved);
        $this->assertTrue($resolved->is($site));
    }

    public function test_resolves_site_by_other_inbound_address(): void
    {
        $site = $this->createSite(['email_inbound_other' => 'other@client.ru']);

        $resolver = new InboundEmailRecipientResolver;

        $resolved = $resolver->resolveSite(['other@client.ru']);

        $this->assertNotNull($resolved);
        $this->assertTrue($resolved->is($site));
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function createSite(array $overrides = []): Site
    {
        $client = AgencyClient::query()->create([
            'name' => 'Test Client',
            'status' => AgencyClientStatus::Active,
        ]);

        return Site::query()->create(array_merge([
            'agency_client_id' => $client->id,
            'name' => 'Test Site',
            'domains' => ['example.com'],
            'timezone' => 'Europe/Moscow',
            'token_hash' => 'hash',
            'status' => SiteStatus::Active,
        ], $overrides));
    }
}
