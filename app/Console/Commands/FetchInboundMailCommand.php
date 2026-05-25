<?php

namespace App\Console\Commands;

use App\Services\InboundImapMailbox;
use Illuminate\Console\Command;

class FetchInboundMailCommand extends Command
{
    protected $signature = 'mail:fetch-inbound';

    protected $description = 'Забрать непрочитанные письма из служебного IMAP-ящика и создать лиды по адресу проекта';

    public function handle(InboundImapMailbox $mailbox): int
    {
        if (! $mailbox->isConfigured()) {
            $this->error('IMAP не настроен. Задайте INBOUND_IMAP_ENABLED=true и учётные данные mail@...');

            return self::FAILURE;
        }

        $this->info('Подключение к '.config('crm.inbound_imap.username').' …');

        $stats = $mailbox->fetchUnread();

        $this->table(
            ['Метрика', 'Кол-во'],
            [
                ['Обработано (лиды)', $stats['processed']],
                ['Пропущено', $stats['skipped']],
                ['Ошибки', $stats['failed']],
            ],
        );

        $this->line('Лог: storage/logs/laravel.log (inbound_imap.*)');

        return self::SUCCESS;
    }
}
