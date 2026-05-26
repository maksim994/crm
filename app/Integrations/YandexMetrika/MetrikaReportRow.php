<?php

namespace App\Integrations\YandexMetrika;

readonly class MetrikaReportRow
{
    /**
     * @param  list<array{id: ?string, name: ?string}>  $dimensions
     * @param  list<float|int|null>  $metrics
     */
    public function __construct(
        public array $dimensions,
        public array $metrics,
    ) {}
}
