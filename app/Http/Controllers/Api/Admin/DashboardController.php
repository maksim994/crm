<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\AgencyClient;
use App\Models\Lead;
use App\Models\Site;
use Illuminate\Http\JsonResponse;

class DashboardController extends Controller
{
    public function __invoke(): JsonResponse
    {
        return response()->json([
            'clients_count' => AgencyClient::query()->count(),
            'sites_count' => Site::query()->count(),
            'leads_count' => Lead::query()->count(),
        ]);
    }
}
