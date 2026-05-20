<?php

namespace App\Support;

use App\Models\Site;

class InboundEmailAddress
{
    public static function forSite(Site $site, ?string $domain = null): string
    {
        $domain ??= config('crm.inbound_domain', 'inbound.local');
        $localPart = config('crm.inbound_local_prefix', 'leads');
        $siteKey = str_replace('-', '', (string) $site->id);

        return "{$localPart}+{$siteKey}@{$domain}";
    }

    public static function resolveSiteIdFromRecipient(string $recipient): ?string
    {
        $recipient = strtolower(trim($recipient));
        $at = strrpos($recipient, '@');

        if ($at === false) {
            return null;
        }

        $local = substr($recipient, 0, $at);
        $prefix = config('crm.inbound_local_prefix', 'leads').'+';

        if (! str_starts_with($local, $prefix)) {
            return null;
        }

        $siteKey = substr($local, strlen($prefix));

        if ($siteKey === '' || ! preg_match('/^[a-f0-9]{32}$/', $siteKey)) {
            return null;
        }

        return substr($siteKey, 0, 8).'-'
            .substr($siteKey, 8, 4).'-'
            .substr($siteKey, 12, 4).'-'
            .substr($siteKey, 16, 4).'-'
            .substr($siteKey, 20, 12);
    }

    public static function findSiteByRecipient(string $recipient): ?Site
    {
        $siteId = static::resolveSiteIdFromRecipient($recipient);

        if ($siteId === null) {
            return null;
        }

        return Site::query()->find($siteId);
    }
}
