<?php

namespace App\Services;

use App\Enums\LeadChannel;
use App\Enums\LeadStatus;
use App\Exceptions\Ingest\InvalidSiteTokenException;
use App\Exceptions\Ingest\SiteNotAcceptingLeadsException;
use App\Integrations\Callibri\CallibriPayloadMapper;
use App\Models\Lead;
use App\Models\Site;
use App\Support\ContactExtractor;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class LeadIngestionService
{
    public function __construct(
        private readonly AdvertisingChannelResolver $advertisingChannelResolver,
        private readonly DuplicateLeadDetector $duplicateLeadDetector,
    ) {}

    /**
     * @param  array<string, mixed>  $payload
     */
    public function ingestFromSeoLead(array $payload, ?string $visitorIp = null): Lead
    {
        $token = trim((string) ($payload['token'] ?? ''));
        $site = $this->resolveSite($token, $visitorIp);
        $data = $this->validateFormPayload($payload);

        $pageUrl = $data['page_url'] ?? null;
        $landingDomain = $this->extractDomain($pageUrl);

        if ($landingDomain !== null && ! $this->domainAllowed($site, $landingDomain)) {
            Log::info('ingest.domain_mismatch', [
                'site_id' => $site->id,
                'landing_domain' => $landingDomain,
            ]);
        }

        $phone = $data['phone'] ?? null;
        $email = $data['email'] ?? null;

        return $this->createLead($site, [
            'channel' => LeadChannel::Form,
            'phone' => $phone,
            'email' => $email,
            'contact_name' => $data['name'] ?? null,
            'form_description' => $data['description'] ?? null,
            'metrika_client_id' => $data['metrika_client_id'] ?? null,
            'utm_source' => $data['utm_source'] ?? null,
            'utm_medium' => $data['utm_medium'] ?? null,
            'utm_campaign' => $data['utm_campaign'] ?? null,
            'utm_term' => $data['utm_term'] ?? null,
            'utm_content' => $data['utm_content'] ?? null,
            'utm_campaign_first' => $data['utm_campaign_first'] ?? null,
            'advertising_channel' => $this->advertisingChannelResolver->resolve(
                LeadChannel::Form,
                $data['utm_medium'] ?? null,
                $data['utm_source'] ?? null,
                $data['metrika_client_id'] ?? null,
            ),
            'landing_domain' => $landingDomain,
            'visitor_ip' => $data['ip'] ?? $visitorIp,
            'is_duplicate' => $this->duplicateLeadDetector->isDuplicate($site, $phone, $email),
            'raw_payload' => $payload,
            'created_at' => now(),
        ]);
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public function ingestFromCall(array $payload, string $token, ?string $visitorIp = null): Lead
    {
        $site = $this->resolveSite($token, $visitorIp);
        $normalized = CallibriPayloadMapper::normalize($payload);
        $data = $this->validateCallPayload($normalized);

        $phone = $data['phone'];
        $createdAt = $data['created_at'] ?? now();

        return $this->createLead($site, [
            'channel' => LeadChannel::Call,
            'phone' => $phone,
            'email' => null,
            'lead_status' => LeadStatus::NotProcessed,
            'metrika_client_id' => $data['metrika_client_id'] ?? null,
            'utm_source' => $data['utm_source'] ?? null,
            'utm_medium' => $data['utm_medium'] ?? null,
            'utm_campaign' => $data['utm_campaign'] ?? null,
            'utm_term' => $data['utm_term'] ?? null,
            'utm_content' => $data['utm_content'] ?? null,
            'advertising_channel' => $this->advertisingChannelResolver->resolve(
                LeadChannel::Call,
                $data['utm_medium'] ?? null,
                $data['utm_source'] ?? null,
                $data['metrika_client_id'] ?? null,
            ),
            'call_recording_url' => $data['call_recording_url'] ?? null,
            'call_duration_sec' => $data['call_duration_sec'] ?? null,
            'visitor_ip' => $visitorIp,
            'is_duplicate' => $this->duplicateLeadDetector->isDuplicate($site, $phone, null),
            'raw_payload' => $payload,
            'created_at' => $createdAt,
        ]);
    }

    public function ingestFromEmail(
        Site $site,
        string $from,
        ?string $subject,
        string $body,
        array $rawPayload = [],
    ): Lead {
        if (! $site->acceptsLeads()) {
            throw new SiteNotAcceptingLeadsException($site);
        }

        $combined = trim(($subject ?? '')."\n".$body);
        $phone = ContactExtractor::phone($combined);
        $email = filter_var($from, FILTER_VALIDATE_EMAIL)
            ? strtolower($from)
            : ContactExtractor::email($combined);

        if ($phone === null && $email === null) {
            throw ValidationException::withMessages([
                'body' => ['Не удалось извлечь телефон или email из письма.'],
            ]);
        }

        $description = trim(($subject ?? '')."\n\n".$body);
        if (strlen($description) > 5000) {
            $description = substr($description, 0, 5000);
        }

        return $this->createLead($site, [
            'channel' => LeadChannel::Email,
            'phone' => $phone,
            'email' => $email,
            'form_description' => $description !== '' ? $description : null,
            'lead_status' => LeadStatus::NotProcessed,
            'advertising_channel' => AdvertisingChannelResolver::NO_DATA,
            'is_duplicate' => $this->duplicateLeadDetector->isDuplicate($site, $phone, $email),
            'raw_payload' => array_merge($rawPayload, [
                'from' => $from,
                'subject' => $subject,
                'body' => $body,
            ]),
            'created_at' => now(),
        ]);
    }

    public function resolveSite(string $token, ?string $visitorIp = null): Site
    {
        $token = trim($token);

        if ($token === '') {
            throw new InvalidSiteTokenException('Token is required.');
        }

        $site = Site::findByToken($token);

        if ($site === null) {
            $tokenPrefix = str_contains($token, ':') ? explode(':', $token, 2)[0] : 'invalid';
            Log::warning('ingest.invalid_token', [
                'ip' => $visitorIp,
                'site_id_prefix' => $tokenPrefix,
            ]);

            throw new InvalidSiteTokenException('Invalid site token.');
        }

        if (! $site->acceptsLeads()) {
            throw new SiteNotAcceptingLeadsException($site);
        }

        return $site;
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function createLead(Site $site, array $attributes): Lead
    {
        return Lead::query()->create(array_merge(['site_id' => $site->id], $attributes));
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    private function validateFormPayload(array $payload): array
    {
        $validator = Validator::make($payload, [
            'phone' => ['nullable', 'string', 'max:32', 'required_without:email'],
            'email' => ['nullable', 'email', 'max:255', 'required_without:phone'],
            'name' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:5000'],
            'metrika_client_id' => ['nullable', 'string', 'max:64'],
            'page_url' => ['nullable', 'string', 'max:2048'],
            'utm_source' => ['nullable', 'string', 'max:255'],
            'utm_medium' => ['nullable', 'string', 'max:255'],
            'utm_campaign' => ['nullable', 'string', 'max:255'],
            'utm_term' => ['nullable', 'string', 'max:255'],
            'utm_content' => ['nullable', 'string', 'max:255'],
            'utm_campaign_first' => ['nullable', 'string', 'max:255'],
            'ip' => ['nullable', 'ip'],
        ], [
            'phone.required_without' => 'Укажите телефон или email.',
            'email.required_without' => 'Укажите телефон или email.',
        ]);

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }

        return $validator->validated();
    }

    /**
     * @param  array<string, mixed>  $normalized
     * @return array<string, mixed>
     */
    private function validateCallPayload(array $normalized): array
    {
        $validator = Validator::make($normalized, [
            'phone' => ['required', 'string', 'max:32'],
            'created_at' => ['nullable', 'date'],
            'utm_source' => ['nullable', 'string', 'max:255'],
            'utm_medium' => ['nullable', 'string', 'max:255'],
            'utm_campaign' => ['nullable', 'string', 'max:255'],
            'utm_term' => ['nullable', 'string', 'max:255'],
            'utm_content' => ['nullable', 'string', 'max:255'],
            'metrika_client_id' => ['nullable', 'string', 'max:64'],
            'call_recording_url' => ['nullable', 'string', 'max:2048'],
            'call_duration_sec' => ['nullable', 'integer', 'min:0'],
        ]);

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }

        $data = $validator->validated();

        if (isset($normalized['created_at']) && $normalized['created_at'] instanceof CarbonInterface) {
            $data['created_at'] = $normalized['created_at'];
        }

        return $data;
    }

    private function extractDomain(?string $pageUrl): ?string
    {
        if ($pageUrl === null || trim($pageUrl) === '') {
            return null;
        }

        $host = parse_url($pageUrl, PHP_URL_HOST);

        return is_string($host) && $host !== '' ? strtolower($host) : null;
    }

    private function domainAllowed(Site $site, string $landingDomain): bool
    {
        $domains = $site->domains ?? [];

        if ($domains === []) {
            return true;
        }

        foreach ($domains as $domain) {
            if (strtolower((string) $domain) === $landingDomain) {
                return true;
            }
        }

        return false;
    }
}
