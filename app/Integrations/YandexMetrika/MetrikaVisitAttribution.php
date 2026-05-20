<?php

namespace App\Integrations\YandexMetrika;

readonly class MetrikaVisitAttribution
{
    public function __construct(
        public ?string $trafficSourceId,
        public ?string $trafficSourceName,
        public ?string $utmCampaign,
    ) {}
}
