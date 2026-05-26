<?php

namespace App\Services;

use App\Exceptions\Client\MetrikaAnalyticsUnavailableException;
use App\Integrations\YandexMetrika\MetrikaBrandFilter;
use App\Integrations\YandexMetrika\MetrikaReportResult;
use App\Integrations\YandexMetrika\MetrikaReportRow;
use App\Integrations\YandexMetrika\MetrikaReportingClient;
use App\Models\MetrikaReportCache;
use App\Models\Site;
use Carbon\Carbon;
use Illuminate\Support\Str;

class MetrikaAnalyticsService
{
    private const CACHE_TTL_HOURS = 4;

    public function __construct(
        private readonly MetrikaReportingClient $reportingClient,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function trafficSources(Site $site, Carbon $dateFrom, Carbon $dateTo, bool $refresh = false): array
    {
        return $this->cachedReport($site, 'traffic_sources', $dateFrom, $dateTo, null, $refresh, function () use ($site, $dateFrom, $dateTo) {
            $result = $this->reportingClient->fetchReport(
                $this->counterId($site),
                ['ym:s:visits', 'ym:s:bounceRate', 'ym:s:pageDepth', 'ym:s:avgVisitDurationSeconds'],
                ['ym:s:trafficSource'],
                $dateFrom,
                $dateTo,
                timezone: $site->timezone,
                limit: 50,
            );

            return $this->buildBreakdownResponse($result);
        });
    }

    /**
     * @return array<string, mixed>
     */
    public function searchEngines(Site $site, Carbon $dateFrom, Carbon $dateTo, string $groupBy, bool $refresh = false): array
    {
        $timeDimension = $groupBy === 'month' ? 'ym:s:startOfMonth' : 'ym:s:date';

        return $this->cachedReport($site, 'search_engines', $dateFrom, $dateTo, $groupBy, $refresh, function () use ($site, $dateFrom, $dateTo, $timeDimension) {
            $summary = $this->reportingClient->fetchReport(
                $this->counterId($site),
                ['ym:s:visits'],
                ['ym:s:searchEngine'],
                $dateFrom,
                $dateTo,
                filters: "ym:s:trafficSource=='organic'",
                timezone: $site->timezone,
                limit: 20,
            );

            $timeseries = $this->reportingClient->fetchReport(
                $this->counterId($site),
                ['ym:s:visits'],
                [$timeDimension, 'ym:s:searchEngine'],
                $dateFrom,
                $dateTo,
                filters: "ym:s:trafficSource=='organic'",
                timezone: $site->timezone,
                limit: 500,
            );

            return array_merge(
                $this->buildBreakdownResponse($summary),
                ['timeseries' => $this->buildTimeseries($timeseries, 0, 1)],
            );
        });
    }

    /**
     * @return array<string, mixed>
     */
    public function searchBranded(Site $site, Carbon $dateFrom, Carbon $dateTo, string $groupBy, bool $refresh = false): array
    {
        return $this->brandedSearchReport($site, $dateFrom, $dateTo, $groupBy, true, $refresh);
    }

    /**
     * @return array<string, mixed>
     */
    public function searchNonBranded(Site $site, Carbon $dateFrom, Carbon $dateTo, string $groupBy, bool $refresh = false): array
    {
        return $this->brandedSearchReport($site, $dateFrom, $dateTo, $groupBy, false, $refresh);
    }

    /**
     * @return array<string, mixed>
     */
    public function geography(Site $site, Carbon $dateFrom, Carbon $dateTo, bool $refresh = false): array
    {
        return $this->cachedReport($site, 'geography', $dateFrom, $dateTo, null, $refresh, function () use ($site, $dateFrom, $dateTo) {
            $result = $this->reportingClient->fetchReport(
                $this->counterId($site),
                ['ym:s:visits'],
                ['ym:s:regionCity'],
                $dateFrom,
                $dateTo,
                timezone: $site->timezone,
                limit: 20,
            );

            return $this->buildBreakdownResponse($result);
        });
    }

    /**
     * @return array<string, mixed>
     */
    public function devices(Site $site, Carbon $dateFrom, Carbon $dateTo, bool $refresh = false): array
    {
        return $this->cachedReport($site, 'devices', $dateFrom, $dateTo, null, $refresh, function () use ($site, $dateFrom, $dateTo) {
            $result = $this->reportingClient->fetchReport(
                $this->counterId($site),
                ['ym:s:visits', 'ym:s:bounceRate', 'ym:s:pageDepth', 'ym:s:avgVisitDurationSeconds'],
                ['ym:s:deviceCategory'],
                $dateFrom,
                $dateTo,
                timezone: $site->timezone,
                limit: 10,
            );

            return $this->buildBreakdownResponse($result);
        });
    }

    /**
     * @param  callable(): array<string, mixed>  $fetcher
     * @return array<string, mixed>
     */
    private function cachedReport(
        Site $site,
        string $reportType,
        Carbon $dateFrom,
        Carbon $dateTo,
        ?string $groupBy,
        bool $refresh,
        callable $fetcher,
    ): array {
        $this->assertAvailable($site);

        if (! $refresh) {
            $cached = MetrikaReportCache::query()
                ->where('site_id', $site->id)
                ->where('report_type', $reportType)
                ->whereDate('date_from', $dateFrom->toDateString())
                ->whereDate('date_to', $dateTo->toDateString())
                ->where('group_by', $groupBy)
                ->where('expires_at', '>', now())
                ->first();

            if ($cached !== null && is_array($cached->payload)) {
                return $cached->payload;
            }
        }

        $payload = $fetcher();

        $cache = MetrikaReportCache::query()->firstOrNew([
            'site_id' => $site->id,
            'report_type' => $reportType,
            'date_from' => $dateFrom->toDateString(),
            'date_to' => $dateTo->toDateString(),
            'group_by' => $groupBy,
        ]);

        if (! $cache->exists) {
            $cache->id = (string) Str::uuid();
        }

        $cache->payload = $payload;
        $cache->fetched_at = now();
        $cache->expires_at = now()->addHours(self::CACHE_TTL_HOURS);
        $cache->save();

        return $payload;
    }

    /**
     * @return array<string, mixed>
     */
    private function brandedSearchReport(
        Site $site,
        Carbon $dateFrom,
        Carbon $dateTo,
        string $groupBy,
        bool $branded,
        bool $refresh,
    ): array {
        $reportType = $branded ? 'search_branded' : 'search_non_branded';
        $timeDimension = $groupBy === 'month' ? 'ym:s:startOfMonth' : 'ym:s:date';
        $brandFilter = MetrikaBrandFilter::organicSearchFilter($site, $branded);

        return $this->cachedReport($site, $reportType, $dateFrom, $dateTo, $groupBy, $refresh, function () use ($site, $dateFrom, $dateTo, $timeDimension, $brandFilter) {
            $summary = $this->reportingClient->fetchReport(
                $this->counterId($site),
                ['ym:s:visits'],
                ['ym:s:searchEngine'],
                $dateFrom,
                $dateTo,
                filters: $brandFilter,
                timezone: $site->timezone,
                limit: 20,
            );

            $timeseries = $this->reportingClient->fetchReport(
                $this->counterId($site),
                ['ym:s:visits'],
                [$timeDimension, 'ym:s:searchEngine'],
                $dateFrom,
                $dateTo,
                filters: $brandFilter,
                timezone: $site->timezone,
                limit: 500,
            );

            return array_merge(
                $this->buildBreakdownResponse($summary),
                ['timeseries' => $this->buildTimeseries($timeseries, 0, 1)],
            );
        });
    }

    private function assertAvailable(Site $site): void
    {
        if (blank($site->metrika_counter_id)) {
            throw new MetrikaAnalyticsUnavailableException(
                'У проекта не указан счётчик Яндекс Метрики.',
            );
        }

        if (! $this->reportingClient->isConfigured()) {
            throw new MetrikaAnalyticsUnavailableException(
                'Reporting API Метрики не настроен. Обратитесь к администратору CRM.',
            );
        }
    }

    private function counterId(Site $site): string
    {
        return trim((string) $site->metrika_counter_id);
    }

    /**
     * @return array<string, mixed>
     */
    private function buildBreakdownResponse(?MetrikaReportResult $result): array
    {
        if ($result === null) {
            return [
                'summary' => ['labels' => [], 'values' => []],
                'table' => [],
            ];
        }

        $labels = [];
        $values = [];
        $table = [];

        foreach ($result->rows as $row) {
            $label = $this->dimensionLabel($row, 0);
            $visits = (int) round($row->metrics[0] ?? 0);

            if ($visits <= 0 && $label === '') {
                continue;
            }

            $labels[] = $label !== '' ? $label : 'Не определено';
            $values[] = $visits;

            $entry = [
                'label' => $label !== '' ? $label : 'Не определено',
                'visits' => $visits,
            ];

            if (count($row->metrics) >= 4) {
                $entry['bounce_rate'] = round($row->metrics[1] ?? 0, 1);
                $entry['page_depth'] = round($row->metrics[2] ?? 0, 2);
                $entry['avg_duration_sec'] = (int) round($row->metrics[3] ?? 0);
            }

            $table[] = $entry;
        }

        return [
            'summary' => [
                'labels' => $labels,
                'values' => $values,
            ],
            'table' => $table,
        ];
    }

    /**
     * @return array{categories: list<string>, series: list<array{name: string, data: list<int>}>}
     */
    private function buildTimeseries(?MetrikaReportResult $result, int $periodIndex, int $seriesIndex): array
    {
        if ($result === null) {
            return ['categories' => [], 'series' => []];
        }

        $categories = [];
        $seriesMap = [];

        foreach ($result->rows as $row) {
            $period = $this->dimensionLabel($row, $periodIndex);
            $seriesName = $this->dimensionLabel($row, $seriesIndex);
            $visits = (int) round($row->metrics[0] ?? 0);

            if ($period === '') {
                continue;
            }

            if (! in_array($period, $categories, true)) {
                $categories[] = $period;
            }

            $key = $seriesName !== '' ? $seriesName : 'Не определено';
            $seriesMap[$key][$period] = ($seriesMap[$key][$period] ?? 0) + $visits;
        }

        $series = [];
        foreach ($seriesMap as $name => $points) {
            $data = [];
            foreach ($categories as $category) {
                $data[] = $points[$category] ?? 0;
            }

            $series[] = ['name' => $name, 'data' => $data];
        }

        return [
            'categories' => $categories,
            'series' => $series,
        ];
    }

    private function dimensionLabel(MetrikaReportRow $row, int $index): string
    {
        $dimension = $row->dimensions[$index] ?? null;

        if (! is_array($dimension)) {
            return '';
        }

        $name = trim((string) ($dimension['name'] ?? ''));

        return $name !== '' ? $name : trim((string) ($dimension['id'] ?? ''));
    }
}
