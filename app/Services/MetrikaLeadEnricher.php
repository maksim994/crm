<?php

namespace App\Services;

use App\Integrations\YandexMetrika\MetrikaReportingClient;
use App\Integrations\YandexMetrika\MetrikaTrafficSourceMapper;
use App\Models\Lead;
use App\Support\MetrikaLog;

class MetrikaLeadEnricher
{
    public function __construct(
        private readonly MetrikaReportingClient $reportingClient,
        private readonly MetrikaTrafficSourceMapper $trafficSourceMapper,
    ) {}

    public function enrich(string $leadId): bool
    {
        if (! $this->reportingClient->isConfigured()) {
            MetrikaLog::warning('metrika.enrich.skip_not_configured', ['lead_id' => $leadId]);

            return false;
        }

        $lead = Lead::query()->with('site')->find($leadId);

        if ($lead === null || $lead->site === null) {
            MetrikaLog::warning('metrika.enrich.lead_not_found', ['lead_id' => $leadId]);

            return false;
        }

        $clientId = trim((string) $lead->metrika_client_id);
        $counterId = trim((string) $lead->site->metrika_counter_id);

        MetrikaLog::info('metrika.enrich.start', [
            'lead_id' => $lead->id,
            'site_id' => $lead->site_id,
            'counter_id' => $counterId,
            'client_id' => $clientId,
            'lead_date' => ($lead->created_at ?? now())->format('Y-m-d'),
            'advertising_channel_before' => $lead->advertising_channel,
        ]);

        if ($clientId === '' || $counterId === '') {
            MetrikaLog::warning('metrika.enrich.skip_missing_ids', [
                'lead_id' => $lead->id,
                'has_client_id' => $clientId !== '',
                'has_counter_id' => $counterId !== '',
            ]);

            return false;
        }

        $attribution = $this->reportingClient->fetchAttributionByClientId(
            $counterId,
            $clientId,
            $lead->created_at ?? now(),
            $lead->site->timezone,
        );

        if ($attribution === null) {
            MetrikaLog::info('metrika.enrich.no_data', [
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
            MetrikaLog::info('metrika.enrich.nothing_to_update', [
                'lead_id' => $lead->id,
                'mapped_channel' => $advertisingChannel,
            ]);

            return false;
        }

        $lead->forceFill($updates)->save();

        MetrikaLog::info('metrika.enrich.applied', [
            'lead_id' => $lead->id,
            'advertising_channel' => $updates['advertising_channel'] ?? $lead->advertising_channel,
            'utm_campaign_first' => $updates['utm_campaign_first'] ?? null,
            'traffic_source_id' => $attribution->trafficSourceId,
            'traffic_source_name' => $attribution->trafficSourceName,
        ]);

        return true;
    }
}
