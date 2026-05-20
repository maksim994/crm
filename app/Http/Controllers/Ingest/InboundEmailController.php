<?php

namespace App\Http\Controllers\Ingest;

use App\Exceptions\Ingest\SiteNotAcceptingLeadsException;
use App\Http\Controllers\Controller;
use App\Models\Site;
use App\Services\LeadIngestionService;
use App\Support\InboundEmailAddress;
use App\Support\InboundEmailPayload;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class InboundEmailController extends Controller
{
    public function store(Request $request, LeadIngestionService $ingestion): JsonResponse
    {
        if (! $this->webhookAuthorized($request)) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        $payload = InboundEmailPayload::fromRequest($request);

        if ($payload === null) {
            return response()->json([
                'message' => 'Invalid payload',
                'hint' => 'Required fields: to (recipient), from (sender). Optional: subject, body/text.',
            ], 422);
        }

        $site = InboundEmailAddress::findSiteByRecipient($payload->to)
            ?? Site::query()->where('email_inbound_address', $payload->to)->first();

        if ($site === null) {
            Log::warning('inbound_email.unknown_recipient', ['to' => $payload->to]);

            return response()->json([
                'message' => 'Unknown recipient',
                'to' => $payload->to,
            ], 404);
        }

        try {
            $lead = $ingestion->ingestFromEmail(
                $site,
                $payload->from,
                $payload->subject,
                $payload->body,
                [
                    'to' => $payload->to,
                    'source' => 'webhook',
                ],
            );
        } catch (SiteNotAcceptingLeadsException) {
            return response()->json(['message' => 'Site is not accepting leads'], 403);
        } catch (ValidationException $exception) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $exception->errors(),
            ], 422);
        }

        Log::info('inbound_email.received', [
            'lead_id' => $lead->id,
            'site_id' => $site->id,
            'to' => $payload->to,
        ]);

        return response()->json([
            'id' => $lead->id,
            'channel' => 'email',
            'is_duplicate' => $lead->is_duplicate,
        ], 201);
    }

    private function webhookAuthorized(Request $request): bool
    {
        $secret = (string) config('crm.inbound_webhook_secret', '');

        if ($secret === '') {
            return true;
        }

        $provided = trim((string) (
            $request->header('X-Inbound-Webhook-Secret')
            ?? $request->query('secret', '')
        ));

        return hash_equals($secret, $provided);
    }
}
