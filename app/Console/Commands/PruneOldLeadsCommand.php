<?php

namespace App\Console\Commands;

use App\Models\Lead;
use Illuminate\Console\Command;

class PruneOldLeadsCommand extends Command
{
    protected $signature = 'leads:prune
                            {--months= : Override LEAD_RETENTION_MONTHS from config}';

    protected $description = 'Delete leads older than the configured retention period';

    public function handle(): int
    {
        $months = (int) ($this->option('months') ?: config('crm.lead_retention_months', 24));

        if ($months < 1) {
            $this->error('Retention months must be at least 1.');

            return self::FAILURE;
        }

        $cutoff = now()->subMonths($months);

        $deleted = Lead::query()
            ->where('created_at', '<', $cutoff)
            ->delete();

        $this->info("Deleted {$deleted} lead(s) older than {$months} month(s) (before {$cutoff->toDateTimeString()}).");

        return self::SUCCESS;
    }
}
