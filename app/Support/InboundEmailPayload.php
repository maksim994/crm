<?php

namespace App\Support;

use Illuminate\Http\Request;

class InboundEmailPayload
{
    public function __construct(
        public readonly string $to,
        public readonly string $from,
        public readonly ?string $subject,
        public readonly string $body,
    ) {}

    public static function fromRequest(Request $request): ?self
    {
        $data = $request->all();

        $to = static::firstNonEmpty($data, [
            'to', 'recipient', 'To', 'to_email',
        ]);

        $from = static::firstNonEmpty($data, [
            'from', 'sender', 'From', 'from_email', 'envelope_from',
        ]);

        $subject = static::firstNonEmpty($data, [
            'subject', 'Subject', 'subject_line',
        ]);

        $body = static::firstNonEmpty($data, [
            'body', 'text', 'body-plain', 'body_plain', 'stripped-text', 'stripped_text',
            'TextBody', 'text_body', 'html', 'body-html', 'body_html',
        ]);

        if ($to === null || $from === null) {
            return null;
        }

        return new self(
            to: $to,
            from: $from,
            subject: $subject,
            body: $body ?? '',
        );
    }

    /**
     * @param  array<string, mixed>  $data
     * @param  list<string>  $keys
     */
    private static function firstNonEmpty(array $data, array $keys): ?string
    {
        foreach ($keys as $key) {
            if (! array_key_exists($key, $data)) {
                continue;
            }

            $value = trim((string) $data[$key]);

            if ($value !== '') {
                return $value;
            }
        }

        return null;
    }
}
