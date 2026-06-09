<?php

namespace App\Integrations\YandexMetrika;

use App\Services\AdvertisingChannelResolver;

class MetrikaTrafficSourceMapper
{
    /**
     * Маппинг источника трафика Метрики → канал трафика в CRM.
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

        if (in_array($id, ['organic', 'search'], true)) {
            return AdvertisingChannelResolver::ORGANIC_SEARCH;
        }

        $paidHints = ['реклам', 'директ', 'advert', 'cpc', 'paid'];
        foreach ($paidHints as $hint) {
            if ($name !== '' && str_contains($name, $hint)) {
                return AdvertisingChannelResolver::ADVERTISING;
            }
        }

        $searchHints = ['поиск', 'поисков', 'search', 'organic'];
        foreach ($searchHints as $hint) {
            if ($name !== '' && str_contains($name, $hint)) {
                return AdvertisingChannelResolver::ORGANIC_SEARCH;
            }
        }

        return AdvertisingChannelResolver::NO_DATA;
    }
}
