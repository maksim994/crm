<?php

namespace App\Integrations\YandexMetrika;

use App\Support\MetrikaLog;
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
     * @return array{query: array<string, string>, url: string}
     */
    public function buildRequestQuery(
        string $counterId,
        string $clientId,
        CarbonInterface $date,
        ?string $timezone = null,
    ): array {
        $escapedClientId = str_replace("'", "\\'", trim($clientId));
        $dateString = $date->format('Y-m-d');

        $query = [
            'ids' => trim($counterId),
            'metrics' => 'ym:s:visits',
            'dimensions' => 'ym:s:trafficSource,ym:s:lastUTMCampaign',
            'filters' => "ym:s:clientID=='{$escapedClientId}'",
            'date1' => $dateString,
            'date2' => $dateString,
            'limit' => '1',
            'lang' => (string) config('metrika.reporting_lang', 'ru'),
        ];

        if (filled($timezone)) {
            $query['timezone'] = $timezone;
        }

        $baseUrl = rtrim((string) config('metrika.reporting_base_url'), '?');

        return [
            'query' => $query,
            'url' => $baseUrl.'?'.http_build_query($query),
        ];
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
            MetrikaLog::warning('metrika.reporting.skip_not_configured');

            return null;
        }

        $clientId = trim($clientId);
        $counterId = trim($counterId);

        if ($clientId === '' || $counterId === '') {
            MetrikaLog::warning('metrika.reporting.skip_missing_ids', [
                'has_client_id' => $clientId !== '',
                'has_counter_id' => $counterId !== '',
            ]);

            return null;
        }

        $built = $this->buildRequestQuery($counterId, $clientId, $date, $timezone);

        MetrikaLog::info('metrika.reporting.request', [
            'counter_id' => $counterId,
            'client_id' => $clientId,
            'date' => $date->format('Y-m-d'),
            'timezone' => $timezone,
            'filters' => $built['query']['filters'],
            'dimensions' => $built['query']['dimensions'],
            'url' => $built['url'],
        ]);

        try {
            $response = Http::withToken((string) config('metrika.oauth_token'), 'OAuth')
                ->acceptJson()
                ->timeout((int) config('metrika.reporting_timeout', 15))
                ->get((string) config('metrika.reporting_base_url'), $built['query']);

            $json = $response->json();

            MetrikaLog::info('metrika.reporting.response', [
                'counter_id' => $counterId,
                'status' => $response->status(),
                'total_rows' => $json['total_rows'] ?? null,
                'sampled' => $json['sampled'] ?? null,
                'rows_returned' => is_array($json['data'] ?? null) ? count($json['data']) : 0,
            ]);

            if (! $response->successful()) {
                MetrikaLog::warning('metrika.reporting.failed', [
                    'counter_id' => $counterId,
                    'status' => $response->status(),
                    'body' => mb_substr($response->body(), 0, 1000),
                ]);

                return null;
            }

            $attribution = $this->parseAttribution($json);

            if ($attribution === null) {
                MetrikaLog::info('metrika.reporting.empty_attribution', [
                    'counter_id' => $counterId,
                    'client_id' => $clientId,
                    'hint' => 'Нет визита с таким Client ID за указанную дату или фильтр не сработал',
                ]);
            } else {
                MetrikaLog::info('metrika.reporting.parsed', [
                    'counter_id' => $counterId,
                    'traffic_source_id' => $attribution->trafficSourceId,
                    'traffic_source_name' => $attribution->trafficSourceName,
                    'utm_campaign' => $attribution->utmCampaign,
                ]);
            }

            return $attribution;
        } catch (\Throwable $exception) {
            MetrikaLog::warning('metrika.reporting.exception', [
                'counter_id' => $counterId,
                'message' => $exception->getMessage(),
            ]);

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
