<?php

namespace App\Support;

use App\Models\Site;

class InboundEmailRecipientResolver
{
    /**
     * Адреса из заголовков и тела (пересылка часто оставляет оригинальный To в X-* или в тексте).
     *
     * @return list<string> lowercase emails
     */
    public function extractAddresses(string $rawHeaders, string $body): array
    {
        $found = [];

        $headerNames = [
            'to',
            'cc',
            'delivered-to',
            'envelope-to',
            'x-original-to',
            'x-forwarded-to',
            'x-real-to',
            'resent-to',
            'x-envelope-to',
        ];

        foreach (preg_split('/\r\n|\n|\r/', $rawHeaders) as $line) {
            if (! str_contains($line, ':')) {
                continue;
            }

            [$name, $value] = explode(':', $line, 2);
            $name = strtolower(trim($name));

            if (! in_array($name, $headerNames, true)) {
                continue;
            }

            foreach ($this->parseEmailList($value) as $email) {
                $found[] = $email;
            }
        }

        $bodySample = mb_substr($body, 0, 8000);
        if (preg_match_all('/(?:^|\n)\s*(?:to|кому)\s*:\s*([^\n<]+)/iu', $bodySample, $matches)) {
            foreach ($matches[1] as $line) {
                foreach ($this->parseEmailList($line) as $email) {
                    $found[] = $email;
                }
            }
        }

        return array_values(array_unique($found));
    }

    /**
     * @param  list<string>  $addresses
     */
    public function resolveSite(array $addresses): ?Site
    {
        $addresses = array_values(array_unique(array_filter(array_map(
            fn (string $email) => strtolower(trim($email)),
            $addresses,
        ))));

        if ($addresses === []) {
            return null;
        }

        foreach ($addresses as $address) {
            $site = Site::query()
                ->whereNotNull('email_inbound_address')
                ->whereRaw('LOWER(email_inbound_address) = ?', [$address])
                ->first();

            if ($site !== null) {
                return $site;
            }
        }

        return null;
    }

    /**
     * @return list<string>
     */
    private function parseEmailList(string $value): array
    {
        $emails = [];

        if (preg_match_all('/[a-z0-9._%+\-]+@[a-z0-9.\-]+\.[a-z]{2,}/i', $value, $matches)) {
            foreach ($matches[0] as $match) {
                $emails[] = strtolower($match);
            }
        }

        return $emails;
    }
}
