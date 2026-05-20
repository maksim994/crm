<?php

namespace App\Services;

use App\Integrations\YandexMetrika\MetrikaReportingClient;
use App\Integrations\YandexMetrika\MetrikaTrafficSourceMapper;
use App\Models\Lead;
use Illuminate\Support\Facades\Log;

class MetrikaLeadEnricher
{
    public function __construct(
        private readonly MetrikaReportingClient $reportingClient,
        private readonly MetrikaTrafficSourceMapper $trafficSourceMapper,
    ) {}

    public function enrich(string $leadId): bool
    {
        if (! $this->reportingClient->isConfigured()) {
            return false;
        }

        $lead = Lead::query()->with('site')->find($leadId);

        if ($lead === null || $lead->site === null) {
            return false;
        }

        $clientId = trim((string) $lead->metrika_client_id);
        $counterId = trim((string) $lead->site->metrika_counter_id);

        if ($clientId === '' || $counterId === '') {
            return false;
        }

        $attribution = $this->reportingClient->fetchAttributionByClientId(
            $counterId,
            $clientId,
            $lead->created_at ?? now(),
            $lead->site->timezone,
        );

        if ($attribution === null) {
            Log::info('metrika.enrich.no_data', [
                'lead_id' => $lead->id,
                'site_id' => $lead->site_id,
            ]);

            return false;
        }

        $advertisingChannel = $this->trafficSourceMapper->toAdvertisingChannel($attribution);

        $updates = [];

        if ($advertisingChannel !== null) {
            $updates['advertising_channel'] = $advertisingChannel;
        }

        if (blank($lead->utm_campaign_first) && filled($attribution->utmCampaign)) {
            $updates['utm_campaign_first'] = $attribution->utmCampaign;
        }

        if ($updates === []) {
            return false;
        }

        $lead->forceFill($updates)->save();

        Log::info('metrika.enrich.applied', [
            'lead_id' => $lead->id,
            'advertising_channel' => $updates['advertising_channel'] ?? $lead->advertising_channel,
            'utm_campaign_first' => array_key_exists('utm_campaign_first', $updates),
        ]);

        return true;
    }
}
