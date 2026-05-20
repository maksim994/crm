<?php

namespace App\Jobs;

use App\Services\MetrikaLeadEnricher;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class EnrichLeadFromMetrikaJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    /** @var list<int> */
    public array $backoff = [30, 120, 300];

    public function __construct(
        public readonly string $leadId,
    ) {}

    public function handle(MetrikaLeadEnricher $enricher): void
    {
        $enricher->enrich($this->leadId);
    }
}
