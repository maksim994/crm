<?php

namespace App\Http\Controllers\Ingest;

use App\Exceptions\Ingest\InvalidSiteTokenException;
use App\Exceptions\Ingest\SiteNotAcceptingLeadsException;
use App\Http\Controllers\Controller;
use App\Services\LeadIngestionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class CallWebhookController extends Controller
{
    public function store(Request $request, LeadIngestionService $ingestion): JsonResponse
    {
        $token = $this->resolveToken($request);

        try {
            $lead = $ingestion->ingestFromCall(
                $request->all(),
                $token,
                $request->ip(),
            );
        } catch (InvalidSiteTokenException) {
            return response()->json(['message' => 'Unauthorized'], 401);
        } catch (SiteNotAcceptingLeadsException) {
            return response()->json(['message' => 'Site is not accepting leads'], 403);
        } catch (ValidationException $exception) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $exception->errors(),
            ], 422);
        }

        return response()->json([
            'id' => $lead->id,
            'is_duplicate' => $lead->is_duplicate,
        ], 201);
    }

    private function resolveToken(Request $request): string
    {
        $token = trim((string) ($request->query('token') ?? $request->header('X-Site-Token', '')));

        if ($token !== '') {
            return $token;
        }

        return trim((string) ($request->input('token') ?? ''));
    }
}
