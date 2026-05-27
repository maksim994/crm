<?php

namespace App\Integrations\YandexMetrika;

readonly class MetrikaVisitAttribution
{
    public function __construct(
        public ?string $trafficSourceId,
        public ?string $trafficSourceName,
        public ?string $utmSource,
        public ?string $utmMedium,
        public ?string $utmCampaign,
        public ?string $utmTerm,
        public ?string $utmContent,
        public ?string $utmCampaignFirst,
    ) {}
}
