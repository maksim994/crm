<?php

namespace App\Http\Controllers\Api\Client;

use App\Exceptions\Client\MetrikaAnalyticsUnavailableException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Client\IndexAnalyticsRequest;
use App\Models\Site;
use App\Services\MetrikaAnalyticsService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;

class AnalyticsController extends Controller
{
    public function __construct(
        private readonly MetrikaAnalyticsService $analytics,
    ) {}

    public function trafficSources(IndexAnalyticsRequest $request, Site $site): JsonResponse
    {
        return $this->respond($request, $site, fn () => $this->analytics->trafficSources(
            $site,
            $this->dateFrom($request),
            $this->dateTo($request),
            $this->refresh($request),
        ));
    }

    public function searchEngines(IndexAnalyticsRequest $request, Site $site): JsonResponse
    {
        return $this->respond($request, $site, fn () => $this->analytics->searchEngines(
            $site,
            $this->dateFrom($request),
            $this->dateTo($request),
            $this->groupBy($request),
            $this->refresh($request),
        ));
    }

    public function searchBranded(IndexAnalyticsRequest $request, Site $site): JsonResponse
    {
        return $this->respond($request, $site, fn () => $this->analytics->searchBranded(
            $site,
            $this->dateFrom($request),
            $this->dateTo($request),
            $this->groupBy($request),
            $this->refresh($request),
        ));
    }

    public function searchNonBranded(IndexAnalyticsRequest $request, Site $site): JsonResponse
    {
        return $this->respond($request, $site, fn () => $this->analytics->searchNonBranded(
            $site,
            $this->dateFrom($request),
            $this->dateTo($request),
            $this->groupBy($request),
            $this->refresh($request),
        ));
    }

    public function geography(IndexAnalyticsRequest $request, Site $site): JsonResponse
    {
        return $this->respond($request, $site, fn () => $this->analytics->geography(
            $site,
            $this->dateFrom($request),
            $this->dateTo($request),
            $this->refresh($request),
        ));
    }

    public function devices(IndexAnalyticsRequest $request, Site $site): JsonResponse
    {
        return $this->respond($request, $site, fn () => $this->analytics->devices(
            $site,
            $this->dateFrom($request),
            $this->dateTo($request),
            $this->refresh($request),
        ));
    }

    /**
     * @param  callable(): array<string, mixed>  $callback
     */
    private function respond(IndexAnalyticsRequest $request, Site $site, callable $callback): JsonResponse
    {
        try {
            return response()->json([
                'data' => $callback(),
                'meta' => [
                    'site_id' => $site->id,
                    'date_from' => $this->dateFrom($request)->toDateString(),
                    'date_to' => $this->dateTo($request)->toDateString(),
                ],
            ]);
        } catch (MetrikaAnalyticsUnavailableException $exception) {
            return response()->json([
                'message' => $exception->getMessage(),
            ], 503);
        }
    }

    private function dateFrom(IndexAnalyticsRequest $request): Carbon
    {
        return Carbon::parse($request->validated('date_from'))->startOfDay();
    }

    private function dateTo(IndexAnalyticsRequest $request): Carbon
    {
        return Carbon::parse($request->validated('date_to'))->endOfDay();
    }

    private function groupBy(IndexAnalyticsRequest $request): string
    {
        return $request->validated('group_by') ?? 'month';
    }

    private function refresh(IndexAnalyticsRequest $request): bool
    {
        return filter_var($request->validated('refresh') ?? false, FILTER_VALIDATE_BOOLEAN);
    }
}
