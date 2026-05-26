<?php

namespace App\Services;

use App\Models\Site;
use App\Support\InboundEmailRecipientResolver;
use Illuminate\Support\Facades\Log;

class InboundImapMailbox
{
    public function __construct(
        private readonly LeadIngestionService $ingestion,
        private readonly InboundEmailRecipientResolver $recipientResolver,
    ) {}

    public function isConfigured(): bool
    {
        return (bool) config('crm.inbound_imap.enabled')
            && filled(config('crm.inbound_imap.username'))
            && filled(config('crm.inbound_imap.password'));
    }

    /**
     * @return array{ok: bool, message: string, hint?: string}
     */
    public function testConnection(): array
    {
        if (! (bool) config('crm.inbound_imap.enabled')) {
            return [
                'ok' => false,
                'message' => 'IMAP отключён (INBOUND_IMAP_ENABLED=false)',
            ];
        }

        if (! filled(config('crm.inbound_imap.username')) || ! filled(config('crm.inbound_imap.password'))) {
            return [
                'ok' => false,
                'message' => 'Не заданы INBOUND_IMAP_USERNAME или INBOUND_IMAP_PASSWORD',
            ];
        }

        if (! function_exists('imap_open')) {
            return [
                'ok' => false,
                'message' => 'PHP ext-imap не установлен',
                'hint' => 'Установите ext-imap в Docker-образ',
            ];
        }

        $mailbox = $this->mailboxConnectionString();
        $inbox = @imap_open(
            $mailbox,
            (string) config('crm.inbound_imap.username'),
            (string) config('crm.inbound_imap.password'),
            OP_READONLY,
            1,
        );

        if ($inbox === false) {
            $error = imap_last_error() ?: 'Неизвестная ошибка IMAP';

            return [
                'ok' => false,
                'message' => 'Не удалось подключиться: '.$error,
                'hint' => 'Проверьте host/port/encryption и пароль',
            ];
        }

        $status = @imap_status($inbox, $mailbox, SA_MESSAGES);
        imap_close($inbox);

        $messages = is_object($status) ? ($status->messages ?? null) : null;
        $suffix = $messages !== null ? " (писем в папке: {$messages})" : '';

        return [
            'ok' => true,
            'message' => 'Подключение к '.config('crm.inbound_imap.host').$suffix,
        ];
    }

    /**
     * @return array{processed: int, skipped: int, failed: int}
     */
    public function fetchUnread(): array
    {
        $stats = ['processed' => 0, 'skipped' => 0, 'failed' => 0];

        if (! $this->isConfigured()) {
            return $stats;
        }

        if (! function_exists('imap_open')) {
            Log::error('inbound_imap.extension_missing', [
                'hint' => 'Install PHP ext-imap in Docker image',
            ]);

            return $stats;
        }

        $mailbox = $this->mailboxConnectionString();
        $inbox = @imap_open(
            $mailbox,
            (string) config('crm.inbound_imap.username'),
            (string) config('crm.inbound_imap.password'),
            0,
            1,
        );

        if ($inbox === false) {
            Log::warning('inbound_imap.connect_failed', [
                'mailbox' => $mailbox,
                'error' => imap_last_error(),
            ]);

            return $stats;
        }

        try {
            $messageNumbers = imap_search($inbox, 'UNSEEN', SE_UID) ?: [];

            foreach ($messageNumbers as $uid) {
                try {
                    $result = $this->processMessage($inbox, (int) $uid);
                    $stats[$result]++;

                    if (config('crm.inbound_imap.mark_read')) {
                        imap_setflag_full($inbox, (string) $uid, '\\Seen', ST_UID);
                    }
                } catch (\Throwable $exception) {
                    $stats['failed']++;
                    Log::warning('inbound_imap.message_failed', [
                        'uid' => $uid,
                        'message' => $exception->getMessage(),
                    ]);
                }
            }
        } finally {
            imap_close($inbox);
        }

        Log::info('inbound_imap.fetch_done', $stats);

        return $stats;
    }

    /**
     * @return 'processed'|'skipped'|'failed'
     */
    private function processMessage($inbox, int $uid): string
    {
        $msgNo = imap_msgno($inbox, $uid);
        $headerInfo = imap_headerinfo($inbox, $msgNo);
        $rawHeaders = (string) imap_fetchheader($inbox, $msgNo);
        $body = $this->fetchMessageBody($inbox, $msgNo);

        $from = $this->formatAddress($headerInfo->fromaddress ?? $headerInfo->from[0] ?? null);
        $subject = isset($headerInfo->subject) ? $this->decodeMimeHeader((string) $headerInfo->subject) : null;

        $recipientAddresses = $this->recipientResolver->extractAddresses($rawHeaders, $body);

        Log::info('inbound_imap.message', [
            'uid' => $uid,
            'from' => $from,
            'subject' => $subject,
            'recipient_candidates' => $recipientAddresses,
        ]);

        $site = $this->recipientResolver->resolveSite($recipientAddresses);

        if ($site === null) {
            $site = $this->defaultSite();

            if ($site === null) {
                Log::warning('inbound_imap.no_site_match', [
                    'uid' => $uid,
                    'recipient_candidates' => $recipientAddresses,
                ]);

                return 'skipped';
            }

            Log::info('inbound_imap.default_site_used', [
                'uid' => $uid,
                'site_id' => $site->id,
            ]);
        }

        if ($from === '') {
            return 'skipped';
        }

        $this->ingestion->ingestFromEmail(
            $site,
            $from,
            $subject,
            $body,
            [
                'imap_uid' => $uid,
                'recipient_candidates' => $recipientAddresses,
                'source' => 'imap',
            ],
        );

        Log::info('inbound_imap.lead_created', [
            'uid' => $uid,
            'site_id' => $site->id,
        ]);

        return 'processed';
    }

    private function defaultSite(): ?Site
    {
        $siteId = (string) config('crm.inbound_imap.default_site_id', '');

        if ($siteId === '') {
            return null;
        }

        return Site::query()->find($siteId);
    }

    private function mailboxConnectionString(): string
    {
        $host = (string) config('crm.inbound_imap.host');
        $port = (int) config('crm.inbound_imap.port', 993);
        $encryption = (string) config('crm.inbound_imap.encryption', 'ssl');
        $folder = (string) config('crm.inbound_imap.folder', 'INBOX');

        $flags = '/imap';
        if ($encryption === 'ssl') {
            $flags .= '/ssl';
        } elseif ($encryption === 'tls') {
            $flags .= '/tls';
        }

        if (! config('crm.inbound_imap.validate_cert', true)) {
            $flags .= '/novalidate-cert';
        }

        return sprintf('{%s:%d%s}%s', $host, $port, $flags, $folder);
    }

    private function fetchMessageBody($inbox, int $msgNo): string
    {
        $structure = imap_fetchstructure($inbox, $msgNo);

        if ($structure === false) {
            return (string) imap_body($inbox, $msgNo);
        }

        $body = $this->extractPartBody($inbox, $msgNo, $structure, '');

        return $body !== '' ? $body : (string) imap_body($inbox, $msgNo);
    }

    /**
     * @param  object  $structure
     */
    private function extractPartBody($inbox, int $msgNo, object $structure, string $partNumber): string
    {
        if ($structure->type === TYPETEXT) {
            $data = (string) imap_fetchbody($inbox, $msgNo, $partNumber ?: '1');

            return $this->decodeBody($data, (int) ($structure->encoding ?? 0));
        }

        if (! empty($structure->parts)) {
            foreach ($structure->parts as $index => $sub) {
                $subPart = $partNumber === '' ? (string) ($index + 1) : $partNumber.'.'.($index + 1);
                $subtype = strtoupper((string) ($sub->subtype ?? ''));

                if ($subtype === 'PLAIN') {
                    return $this->extractPartBody($inbox, $msgNo, $sub, $subPart);
                }
            }

            foreach ($structure->parts as $index => $sub) {
                $subPart = $partNumber === '' ? (string) ($index + 1) : $partNumber.'.'.($index + 1);
                $text = $this->extractPartBody($inbox, $msgNo, $sub, $subPart);

                if ($text !== '') {
                    return $text;
                }
            }
        }

        return '';
    }

    private function decodeBody(string $data, int $encoding): string
    {
        return match ($encoding) {
            ENCBASE64 => (string) base64_decode($data),
            ENCBINARY => $data,
            ENCQUOTEDPRINTABLE => (string) quoted_printable_decode($data),
            default => $data,
        };
    }

    private function decodeMimeHeader(string $value): string
    {
        $decoded = imap_utf8($value);

        return is_string($decoded) ? $decoded : $value;
    }

    private function formatAddress(mixed $from): string
    {
        if (is_string($from)) {
            $emails = [];
            if (preg_match('/[a-z0-9._%+\-]+@[a-z0-9.\-]+\.[a-z]{2,}/i', $from, $match)) {
                return strtolower($match[0]);
            }

            return '';
        }

        if (is_array($from) && isset($from[0])) {
            return $this->formatAddress($from[0]);
        }

        if (is_object($from) && isset($from->mailbox, $from->host)) {
            return strtolower($from->mailbox.'@'.$from->host);
        }

        return '';
    }
}
