<?php

namespace App\Integrations\YandexMetrika;

use App\Exceptions\Client\MetrikaAnalyticsUnavailableException;
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
     * @param  list<string>  $metrics
     * @param  list<string>  $dimensions
     */
    public function fetchReport(
        string $counterId,
        array $metrics,
        array $dimensions,
        CarbonInterface $dateFrom,
        CarbonInterface $dateTo,
        ?string $filters = null,
        ?string $timezone = null,
        int $limit = 100,
    ): ?MetrikaReportResult {
        if (! $this->isConfigured()) {
            MetrikaLog::warning('metrika.reporting.skip_not_configured');

            return null;
        }

        $counterId = trim($counterId);

        if ($counterId === '' || $metrics === [] || $dimensions === []) {
            return null;
        }

        $query = [
            'ids' => $counterId,
            'metrics' => implode(',', $metrics),
            'dimensions' => implode(',', $dimensions),
            'date1' => $dateFrom->format('Y-m-d'),
            'date2' => $dateTo->format('Y-m-d'),
            'limit' => (string) max(1, min($limit, 10000)),
            'lang' => (string) config('metrika.reporting_lang', 'ru'),
        ];

        if ($filters !== null && trim($filters) !== '') {
            $query['filters'] = $filters;
        }

        if (filled($timezone)) {
            $query['timezone'] = $timezone;
        }

        MetrikaLog::info('metrika.reporting.aggregate_request', [
            'counter_id' => $counterId,
            'metrics' => $query['metrics'],
            'dimensions' => $query['dimensions'],
            'date1' => $query['date1'],
            'date2' => $query['date2'],
            'filters' => $filters,
        ]);

        try {
            $response = Http::withToken((string) config('metrika.oauth_token'), 'OAuth')
                ->acceptJson()
                ->timeout((int) config('metrika.reporting_timeout', 15))
                ->get((string) config('metrika.reporting_base_url'), $query);

            $json = $response->json();

            if (! $response->successful()) {
                MetrikaLog::warning('metrika.reporting.aggregate_failed', [
                    'counter_id' => $counterId,
                    'status' => $response->status(),
                    'body' => mb_substr($response->body(), 0, 1000),
                ]);

                throw new MetrikaAnalyticsUnavailableException(
                    $this->formatHttpErrorMessage($response->status(), is_array($json) ? $json : null, $counterId),
                );
            }

            return $this->parseReport($json);
        } catch (MetrikaAnalyticsUnavailableException $exception) {
            throw $exception;
        } catch (\Throwable $exception) {
            MetrikaLog::warning('metrika.reporting.aggregate_exception', [
                'counter_id' => $counterId,
                'message' => $exception->getMessage(),
            ]);

            Log::warning('metrika.reporting.aggregate_exception', [
                'counter_id' => $counterId,
                'message' => $exception->getMessage(),
            ]);

            throw new MetrikaAnalyticsUnavailableException(
                'Не удалось получить данные из Яндекс Метрики. Попробуйте позже.',
                previous: $exception,
            );
        }
    }

    /**
     * @param  array<string, mixed>|null  $payload
     */
    public function parseReport(?array $payload): ?MetrikaReportResult
    {
        $rows = $payload['data'] ?? null;

        if (! is_array($rows)) {
            return null;
        }

        $parsedRows = [];

        foreach ($rows as $row) {
            if (! is_array($row)) {
                continue;
            }

            $dimensions = [];
            foreach ($row['dimensions'] ?? [] as $dimension) {
                if (! is_array($dimension)) {
                    continue;
                }

                $dimensions[] = [
                    'id' => isset($dimension['id']) ? (string) $dimension['id'] : null,
                    'name' => isset($dimension['name']) ? (string) $dimension['name'] : null,
                ];
            }

            $metrics = [];
            foreach ($row['metrics'] ?? [] as $metric) {
                $metrics[] = is_numeric($metric) ? (float) $metric : null;
            }

            $parsedRows[] = new MetrikaReportRow($dimensions, $metrics);
        }

        $metricNames = [];
        foreach ($payload['query']['metrics'] ?? [] as $metricName) {
            if (is_string($metricName)) {
                $metricNames[] = $metricName;
            }
        }

        return new MetrikaReportResult(
            rows: $parsedRows,
            totalRows: (int) ($payload['total_rows'] ?? count($parsedRows)),
            metricNames: $metricNames,
        );
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
    private function formatHttpErrorMessage(int $status, ?array $payload, string $counterId): string
    {
        $apiMessage = null;

        if (is_array($payload['errors'] ?? null) && $payload['errors'] !== []) {
            $first = $payload['errors'][0] ?? null;
            if (is_array($first) && filled($first['message'] ?? null)) {
                $apiMessage = (string) $first['message'];
            }
        }

        if ($apiMessage === null && filled($payload['message'] ?? null)) {
            $apiMessage = (string) $payload['message'];
        }

        if ($status === 403) {
            return 'Нет доступа к счётчику Метрики '.$counterId
                .'. Проверьте OAuth-токен (право metrika:read) и доступ к счётчику.';
        }

        if ($status === 401) {
            return 'OAuth-токен Метрики недействителен или просрочен. Обновите METRIKA_OAUTH_TOKEN.';
        }

        if ($apiMessage !== null) {
            return 'Ошибка Reporting API Метрики: '.$apiMessage;
        }

        return 'Reporting API Метрики вернул ошибку HTTP '.$status.'.';
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
