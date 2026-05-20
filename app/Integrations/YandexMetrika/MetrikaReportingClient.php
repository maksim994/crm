<?php

namespace App\Integrations\YandexMetrika;

use Carbon\CarbonInterface;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class MetrikaReportingClient
{
    public function isConfigured(): bool
    {
        return config('metrika.reporting_enabled')
            && filled(config('metrika.oauth_token'));
    }

    /**
     * Источник трафика и UTM по Client ID за дату визита (день создания лида).
     */
    public function fetchAttributionByClientId(
        string $counterId,
        string $clientId,
        CarbonInterface $date,
        ?string $timezone = null,
    ): ?MetrikaVisitAttribution {
        if (! $this->isConfigured()) {
            return null;
        }

        $clientId = trim($clientId);
        $counterId = trim($counterId);

        if ($clientId === '' || $counterId === '') {
            return null;
        }

        $escapedClientId = str_replace("'", "\\'", $clientId);
        $dateString = $date->format('Y-m-d');

        $query = [
            'ids' => $counterId,
            'metrics' => 'ym:s:visits',
            'dimensions' => 'ym:s:trafficSource,ym:s:lastUTMCampaign',
            'filters' => "ym:s:clientID=='{$escapedClientId}'",
            'date1' => $dateString,
            'date2' => $dateString,
            'limit' => 1,
            'lang' => config('metrika.reporting_lang', 'ru'),
        ];

        if (filled($timezone)) {
            $query['timezone'] = $timezone;
        }

        try {
            $response = Http::withToken((string) config('metrika.oauth_token'), 'OAuth')
                ->acceptJson()
                ->timeout((int) config('metrika.reporting_timeout', 15))
                ->get((string) config('metrika.reporting_base_url'), $query);

            if (! $response->successful()) {
                Log::warning('metrika.reporting.failed', [
                    'counter_id' => $counterId,
                    'status' => $response->status(),
                    'body' => mb_substr($response->body(), 0, 500),
                ]);

                return null;
            }

            return $this->parseAttribution($response->json());
        } catch (\Throwable $exception) {
            Log::warning('metrika.reporting.exception', [
                'counter_id' => $counterId,
                'message' => $exception->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * @param  array<string, mixed>|null  $payload
     */
    private function parseAttribution(?array $payload): ?MetrikaVisitAttribution
    {
        $rows = $payload['data'] ?? null;

        if (! is_array($rows) || $rows === []) {
            return null;
        }

        $dimensions = $rows[0]['dimensions'] ?? null;

        if (! is_array($dimensions) || $dimensions === []) {
            return null;
        }

        $traffic = $dimensions[0] ?? [];
        $utm = $dimensions[1] ?? [];

        $trafficId = is_array($traffic) ? ($traffic['id'] ?? null) : null;
        $trafficName = is_array($traffic) ? ($traffic['name'] ?? null) : null;
        $utmCampaign = is_array($utm) ? ($utm['name'] ?? $utm['id'] ?? null) : null;

        return new MetrikaVisitAttribution(
            trafficSourceId: is_string($trafficId) ? $trafficId : null,
            trafficSourceName: is_string($trafficName) ? $trafficName : null,
            utmCampaign: is_string($utmCampaign) ? $utmCampaign : null,
        );
    }
}
