<?php

namespace App\Http\Controllers\Api\Admin;

use App\Exceptions\Ingest\SiteNotAcceptingLeadsException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreLeadRequest;
use App\Http\Resources\Admin\LeadResource;
use App\Models\Lead;
use App\Models\Site;
use App\Services\LeadIngestionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class LeadController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $leads = Lead::query()
            ->with(['site.agencyClient'])
            ->when($request->filled('agency_client_id'), function ($q) use ($request) {
                $q->whereHas('site', fn ($sq) => $sq->where('agency_client_id', $request->string('agency_client_id')));
            })
            ->when($request->filled('site_id'), fn ($q) => $q->where('site_id', $request->string('site_id')))
            ->when($request->filled('channel'), fn ($q) => $q->where('channel', $request->string('channel')))
            ->when($request->filled('lead_status'), fn ($q) => $q->where('lead_status', $request->string('lead_status')))
            ->orderByDesc('created_at')
            ->paginate(30);

        return LeadResource::collection($leads);
    }

    public function show(Lead $lead): LeadResource
    {
        $lead->load('site.agencyClient');

        return new LeadResource($lead);
    }

    public function store(StoreLeadRequest $request, LeadIngestionService $ingestion): JsonResponse|LeadResource
    {
        $site = Site::query()->findOrFail($request->validated('site_id'));

        try {
            $lead = $ingestion->createManual($site, $request->validated());
        } catch (SiteNotAcceptingLeadsException) {
            return response()->json(['message' => 'Site is not accepting leads'], 403);
        }

        return (new LeadResource($lead->load('site.agencyClient')))
            ->response()
            ->setStatusCode(201);
    }

    public function update(Request $request, Lead $lead): LeadResource
    {
        $data = $request->validate([
            'lead_status' => ['required', 'string'],
            'manager_name' => ['nullable', 'string', 'max:255'],
            'manager_comment' => ['nullable', 'string'],
            'acc_status' => ['nullable', 'string', 'max:255'],
            'acc_comment' => ['nullable', 'string'],
            'ppc_status' => ['nullable', 'string', 'max:255'],
            'ppc_comment' => ['nullable', 'string'],
        ]);

        $lead->update($data);

        return new LeadResource($lead->fresh()->load('site.agencyClient'));
    }
}
