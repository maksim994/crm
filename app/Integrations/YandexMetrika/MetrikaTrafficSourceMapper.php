<?php

namespace App\Integrations\YandexMetrika;

use App\Services\AdvertisingChannelResolver;

class MetrikaTrafficSourceMapper
{
    /**
     * Маппинг источника трафика Метрики → рекламный канал в CRM (ТЗ §9.2).
     */
    public function toAdvertisingChannel(?MetrikaVisitAttribution $attribution): ?string
    {
        if ($attribution === null) {
            return null;
        }

        $id = strtolower(trim($attribution->trafficSourceId ?? ''));
        $name = mb_strtolower(trim($attribution->trafficSourceName ?? ''));

        if ($id === 'ad') {
            return AdvertisingChannelResolver::ADVERTISING;
        }

        $paidHints = ['реклам', 'директ', 'advert', 'cpc', 'paid'];
        foreach ($paidHints as $hint) {
            if ($name !== '' && str_contains($name, $hint)) {
                return AdvertisingChannelResolver::ADVERTISING;
            }
        }

        return AdvertisingChannelResolver::NO_DATA;
    }
}
