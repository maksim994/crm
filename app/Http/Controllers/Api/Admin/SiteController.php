<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\Admin\SiteResource;
use App\Models\Site;
use App\Support\InboundEmailAddress;
use App\Support\SiteIntegration;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class SiteController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $sites = Site::query()
            ->with('agencyClient')
            ->withCount('leads')
            ->when($request->filled('agency_client_id'), fn ($q) => $q->where('agency_client_id', $request->string('agency_client_id')))
            ->orderBy('name')
            ->paginate(20);

        return SiteResource::collection($sites);
    }

    public function show(Site $site): JsonResponse
    {
        $site->load('agencyClient')->loadCount('leads');

        return response()->json([
            'data' => new SiteResource($site),
            'integration' => SiteIntegration::instructions($site),
            'ingest_url' => SiteIntegration::ingestUrl(),
            'call_webhook_url' => SiteIntegration::callWebhookUrl(),
            'inbound_email' => $site->email_inbound_address ?? InboundEmailAddress::forSite($site),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $this->validated($request);
        $data['token_hash'] = '';
        $site = Site::query()->create($data);
        $token = $site->issueToken();

        if ($site->email_inbound_address === null) {
            $site->update([
                'email_inbound_address' => InboundEmailAddress::forSite($site),
            ]);
            $site->refresh();
        }

        return response()->json([
            'data' => new SiteResource($site->load('agencyClient')),
            'token' => $token,
            'integration' => SiteIntegration::instructions($site),
        ], 201);
    }

    public function update(Request $request, Site $site): SiteResource
    {
        $site->update($this->validated($request));

        return new SiteResource($site->fresh()->load('agencyClient'));
    }

    public function destroy(Site $site): JsonResponse
    {
        $site->delete();

        return response()->json(['message' => 'deleted']);
    }

    public function regenerateToken(Site $site): JsonResponse
    {
        $token = $site->issueToken();

        return response()->json([
            'token' => $token,
            'integration' => SiteIntegration::instructions($site),
            'site' => new SiteResource($site->load('agencyClient')),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function validated(Request $request): array
    {
        $data = $request->validate([
            'agency_client_id' => ['required', 'uuid', 'exists:agency_clients,id'],
            'name' => ['required', 'string', 'max:255'],
            'domains' => ['required', 'array', 'min:1'],
            'domains.*' => ['required', 'string', 'max:255'],
            'metrika_counter_id' => ['nullable', 'string', 'max:32'],
            'timezone' => ['required', 'string', 'max:64'],
            'status' => ['required', 'in:active,paused,archived'],
            'email_inbound_address' => ['nullable', 'email', 'max:255'],
        ]);

        $data['domains'] = array_values(array_filter(array_map('trim', $data['domains'])));

        return $data;
    }
}
