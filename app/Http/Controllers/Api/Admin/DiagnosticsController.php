<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Site;
use App\Services\IntegrationDiagnosticsService;
use Illuminate\Http\JsonResponse;

class DiagnosticsController extends Controller
{
    public function __construct(
        private readonly IntegrationDiagnosticsService $diagnostics,
    ) {}

    public function index(): JsonResponse
    {
        return response()->json($this->diagnostics->platform());
    }

    public function site(Site $site): JsonResponse
    {
        return response()->json($this->diagnostics->site($site));
    }
}
