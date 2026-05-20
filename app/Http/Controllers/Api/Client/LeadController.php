<?php

namespace App\Http\Controllers\Api\Client;

use App\Http\Controllers\Controller;
use App\Http\Requests\Client\IndexLeadsRequest;
use App\Http\Resources\Client\LeadResource;
use App\Models\Lead;
use App\Services\ClientLeadCsvExporter;
use App\Support\ClientLeadQuery;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Symfony\Component\HttpFoundation\StreamedResponse;

class LeadController extends Controller
{
    use AuthorizesRequests;

    public function index(IndexLeadsRequest $request): AnonymousResourceCollection
    {
        $this->authorize('viewAny', Lead::class);

        $leads = ClientLeadQuery::forUser($request->user(), $request)->paginate(30);

        return LeadResource::collection($leads);
    }

    public function show(Request $request, Lead $lead): LeadResource
    {
        $this->authorize('view', $lead);
        $lead->load('site');

        return new LeadResource($lead);
    }

    public function export(IndexLeadsRequest $request, ClientLeadCsvExporter $exporter): StreamedResponse
    {
        $this->authorize('viewAny', Lead::class);

        $query = ClientLeadQuery::forUser($request->user(), $request);

        $filename = 'leads-'.now()->format('Y-m-d').'.csv';

        return $exporter->download($query, $filename);
    }
}
