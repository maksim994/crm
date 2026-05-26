<?php

namespace App\Integrations\YandexMetrika;

readonly class MetrikaReportResult
{
    /**
     * @param  list<MetrikaReportRow>  $rows
     * @param  list<string>  $metricNames
     */
    public function __construct(
        public array $rows,
        public int $totalRows,
        public array $metricNames = [],
    ) {}
}
