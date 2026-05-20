<?php

namespace App\Jobs;

use App\Services\MetrikaLeadEnricher;
use App\Support\MetrikaLog;
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
        MetrikaLog::info('metrika.job.start', ['lead_id' => $this->leadId]);

        $applied = $enricher->enrich($this->leadId);

        MetrikaLog::info('metrika.job.done', [
            'lead_id' => $this->leadId,
            'applied' => $applied,
        ]);
    }
}
