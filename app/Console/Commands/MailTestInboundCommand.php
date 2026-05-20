<?php

namespace App\Console\Commands;

use App\Jobs\ProcessInboundEmailJob;
use App\Models\Site;
use App\Support\InboundEmailAddress;
use Illuminate\Console\Command;

class MailTestInboundCommand extends Command
{
    protected $signature = 'mail:test-inbound
        {site : UUID сайта}
        {--from=demo@client.test : Email отправителя}
        {--subject=Заявка с почты : Тема письма}
        {--body=Телефон +79001234567 : Текст письма}
        {--sync : Выполнить синхронно без очереди}';

    protected $description = 'Создать лид из тестового входящего письма (dev)';

    public function handle(): int
    {
        $site = Site::query()->findOrFail($this->argument('site'));

        if ($site->email_inbound_address === null) {
            $site->update([
                'email_inbound_address' => InboundEmailAddress::forSite($site),
            ]);
            $site->refresh();
        }

        $job = new ProcessInboundEmailJob(
            to: $site->email_inbound_address,
            from: (string) $this->option('from'),
            subject: (string) $this->option('subject'),
            body: (string) $this->option('body'),
        );

        if ($this->option('sync')) {
            $job->handle(app(\App\Services\LeadIngestionService::class));
        } else {
            dispatch($job);
        }

        $this->info('Inbound email processed for site: '.$site->name);
        $this->line('Recipient: '.$site->email_inbound_address);

        return self::SUCCESS;
    }
}
