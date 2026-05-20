<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\Admin\LeadResource;
use App\Models\Lead;
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
