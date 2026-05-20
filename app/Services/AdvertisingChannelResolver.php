<?php

namespace App\Services;

use App\Enums\LeadChannel;

class AdvertisingChannelResolver
{
    public const ADVERTISING = 'Переходы по рекламе';

    public const NO_DATA = 'Нет данных';

    public function resolve(
        LeadChannel $channel,
        ?string $utmMedium,
        ?string $utmSource,
        ?string $metrikaClientId,
    ): string {
        if ($channel !== LeadChannel::Form) {
            return self::NO_DATA;
        }

        $medium = strtolower(trim($utmMedium ?? ''));
        $source = strtolower(trim($utmSource ?? ''));

        if ($medium === 'cpc') {
            return self::ADVERTISING;
        }

        if ($source !== '' && (str_contains($source, 'yandex') || str_contains($source, 'google'))) {
            return self::ADVERTISING;
        }

        return self::NO_DATA;
    }
}
