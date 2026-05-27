<?php

namespace App\Jobs;

use App\Models\Site;
use App\Services\LeadIngestionService;
use App\Support\InboundEmailAddress;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class ProcessInboundEmailJob implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public readonly string $to,
        public readonly string $from,
        public readonly ?string $subject,
        public readonly string $body,
    ) {}

    public function handle(LeadIngestionService $ingestion): void
    {
        $site = InboundEmailAddress::findSiteByRecipient($this->to);

        if ($site === null) {
            $site = Site::findByInboundEmail($this->to);
        }

        if ($site === null) {
            Log::warning('inbound_email.unknown_recipient', ['to' => $this->to]);

            return;
        }

        $ingestion->ingestFromEmail(
            $site,
            $this->from,
            $this->subject,
            $this->body,
            ['to' => $this->to],
        );
    }
}
