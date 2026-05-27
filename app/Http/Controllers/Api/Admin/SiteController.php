<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\Admin\SiteResource;
use App\Models\Site;
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
            'token' => $site->plainToken(),
            'integration' => SiteIntegration::instructions($site),
            'ingest_url' => SiteIntegration::ingestUrl(),
            'call_webhook_url' => SiteIntegration::callWebhookUrl(),
            'inbound_email' => $site->email_inbound_address,
            'embed_script_url' => SiteIntegration::embedScriptUrl(),
            'inbound_email_webhook_url' => SiteIntegration::inboundEmailWebhookUrl(),
            'embed_script_tag' => SiteIntegration::embedScriptTag($site->plainToken()),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $this->validated($request);
        $data['token_hash'] = '';
        $site = Site::query()->create($data);
        $token = $site->issueToken();

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
            'embed_script_tag' => SiteIntegration::embedScriptTag($token),
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
            'metrika_brand_keywords' => ['nullable', 'array'],
            'metrika_brand_keywords.*' => ['string', 'max:100'],
            'timezone' => ['required', 'string', 'max:64'],
            'status' => ['required', 'in:active,paused,archived'],
            'email_inbound_address' => ['nullable', 'email', 'max:255'],
            'email_inbound_seo' => ['nullable', 'email', 'max:255'],
            'email_inbound_other' => ['nullable', 'email', 'max:255'],
        ]);

        $data['domains'] = array_values(array_filter(array_map('trim', $data['domains'])));

        if (array_key_exists('metrika_brand_keywords', $data)) {
            $data['metrika_brand_keywords'] = array_values(array_filter(array_map(
                static fn ($keyword) => trim((string) $keyword),
                $data['metrika_brand_keywords'] ?? [],
            )));
        }

        foreach (['email_inbound_address', 'email_inbound_seo', 'email_inbound_other'] as $field) {
            if (! empty($data[$field])) {
                $data[$field] = strtolower(trim((string) $data[$field]));
            } else {
                $data[$field] = null;
            }
        }

        return $data;
    }
}
