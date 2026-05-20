<?php

namespace Tests\Unit;

use App\Enums\AgencyClientStatus;
use App\Enums\LeadChannel;
use App\Enums\LeadStatus;
use App\Enums\SiteStatus;
use App\Models\AgencyClient;
use App\Models\Lead;
use App\Models\Site;
use App\Services\DuplicateLeadDetector;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class DuplicateLeadDetectorTest extends TestCase
{
    use RefreshDatabase;

    private DuplicateLeadDetector $detector;

    private Site $site;

    protected function setUp(): void
    {
        parent::setUp();

        $this->detector = new DuplicateLeadDetector;

        $client = AgencyClient::query()->create([
            'name' => 'Dup Client',
            'status' => AgencyClientStatus::Active,
        ]);

        $this->site = Site::query()->create([
            'agency_client_id' => $client->id,
            'name' => 'Dup Site',
            'domains' => ['dup.example.com'],
            'timezone' => 'Europe/Moscow',
            'token_hash' => '',
            'status' => SiteStatus::Active,
        ]);
    }

    public function test_detects_duplicate_phone_within_30_days(): void
    {
        Lead::query()->create([
            'site_id' => $this->site->id,
            'channel' => LeadChannel::Form,
            'phone' => '+7 (900) 111-22-33',
            'lead_status' => LeadStatus::NotProcessed,
            'created_at' => now(),
        ]);

        $this->assertTrue(
            $this->detector->isDuplicate($this->site, '89001112233', null)
        );
    }

    public function test_detects_duplicate_email(): void
    {
        Lead::query()->create([
            'site_id' => $this->site->id,
            'channel' => LeadChannel::Form,
            'email' => 'User@Example.com',
            'lead_status' => LeadStatus::NotProcessed,
            'created_at' => now(),
        ]);

        $this->assertTrue(
            $this->detector->isDuplicate($this->site, null, 'user@example.com')
        );
    }

    public function test_no_duplicate_when_contact_missing(): void
    {
        $this->assertFalse(
            $this->detector->isDuplicate($this->site, null, null)
        );
    }

    public function test_old_lead_outside_window_is_not_duplicate(): void
    {
        $lead = Lead::query()->create([
            'site_id' => $this->site->id,
            'channel' => LeadChannel::Form,
            'phone' => '+79001112233',
            'lead_status' => LeadStatus::NotProcessed,
            'created_at' => now(),
        ]);

        $lead->forceFill(['created_at' => now()->subDays(31)])->save();

        $this->assertFalse(
            $this->detector->isDuplicate($this->site, '+79001112233', null)
        );
    }

    public function test_respects_custom_before_timestamp(): void
    {
        Lead::query()->create([
            'site_id' => $this->site->id,
            'channel' => LeadChannel::Form,
            'phone' => '+79001112233',
            'lead_status' => LeadStatus::NotProcessed,
            'created_at' => now(),
        ]);

        $before = Carbon::parse('2026-01-15 12:00:00');

        $this->assertFalse(
            $this->detector->isDuplicate($this->site, '+79001112233', null, $before)
        );
    }
}
