<?php

namespace App\Http\Controllers\Ingest;

use App\Exceptions\Ingest\InvalidSiteTokenException;
use App\Exceptions\Ingest\SiteNotAcceptingLeadsException;
use App\Http\Controllers\Controller;
use App\Services\LeadIngestionService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Validation\ValidationException;

class SeoLeadController extends Controller
{
    public function store(Request $request, LeadIngestionService $ingestion): Response|\Illuminate\Http\JsonResponse
    {
        try {
            $lead = $ingestion->ingestFromSeoLead(
                $request->all(),
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

        $accept = $request->header('Accept', '');

        if ($accept !== '' && str_contains($accept, 'text/plain') && ! str_contains($accept, 'application/json')) {
            return response($lead->id, 201, ['Content-Type' => 'text/plain; charset=UTF-8']);
        }

        return response()->json(['id' => $lead->id], 201);
    }
}
