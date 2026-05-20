<?php

namespace App\Integrations\Callibri;

use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;

class CallibriPayloadMapper
{
    /**
     * @param  array<string, mixed>  $payload
     * @return array{
     *     phone: string,
     *     created_at?: CarbonInterface|null,
     *     utm_source?: string|null,
     *     utm_medium?: string|null,
     *     utm_campaign?: string|null,
     *     utm_term?: string|null,
     *     utm_content?: string|null,
     *     metrika_client_id?: string|null,
     *     call_recording_url?: string|null,
     *     call_duration_sec?: int|null,
     * }
     */
    public static function normalize(array $payload): array
    {
        if (isset($payload['caller_phone']) || isset($payload['record_url']) || isset($payload['duration'])) {
            return static::fromCallibri($payload);
        }

        return static::fromNormalized($payload);
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    private static function fromCallibri(array $payload): array
    {
        $campaign = trim((string) ($payload['campaign'] ?? ''));

        return [
            'phone' => trim((string) ($payload['caller_phone'] ?? $payload['phone'] ?? '')),
            'created_at' => static::parseDateTime($payload['call_time'] ?? $payload['created_at'] ?? null),
            'utm_campaign' => $campaign !== '' ? $campaign : null,
            'utm_source' => $payload['utm_source'] ?? null,
            'utm_medium' => $payload['utm_medium'] ?? null,
            'utm_term' => $payload['utm_term'] ?? null,
            'utm_content' => $payload['utm_content'] ?? null,
            'metrika_client_id' => $payload['metrika_client_id'] ?? null,
            'call_recording_url' => $payload['record_url'] ?? $payload['call_recording_url'] ?? null,
            'call_duration_sec' => isset($payload['duration'])
                ? (int) $payload['duration']
                : (isset($payload['call_duration_sec']) ? (int) $payload['call_duration_sec'] : null),
        ];
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    private static function fromNormalized(array $payload): array
    {
        return [
            'phone' => trim((string) ($payload['phone'] ?? '')),
            'created_at' => static::parseDateTime($payload['created_at'] ?? null),
            'utm_source' => $payload['utm_source'] ?? null,
            'utm_medium' => $payload['utm_medium'] ?? null,
            'utm_campaign' => $payload['utm_campaign'] ?? null,
            'utm_term' => $payload['utm_term'] ?? null,
            'utm_content' => $payload['utm_content'] ?? null,
            'metrika_client_id' => $payload['metrika_client_id'] ?? null,
            'call_recording_url' => $payload['call_recording_url'] ?? null,
            'call_duration_sec' => isset($payload['call_duration_sec'])
                ? (int) $payload['call_duration_sec']
                : null,
        ];
    }

    private static function parseDateTime(mixed $value): ?CarbonInterface
    {
        if ($value === null || $value === '') {
            return null;
        }

        try {
            return Carbon::parse((string) $value);
        } catch (\Throwable) {
            return null;
        }
    }
}
