<?php

namespace App\Console\Commands;

use App\Integrations\YandexMetrika\MetrikaReportingClient;
use App\Models\Lead;
use App\Services\MetrikaLeadEnricher;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Config;

class MetrikaTestLeadCommand extends Command
{
    protected $signature = 'metrika:test-lead
                            {lead : UUID лида в CRM}
                            {--show-url : Показать URL запроса к Reporting API без вызова enrich}';

    protected $description = 'Проверить запрос в Яндекс.Метрику для лида (синхронно, с логом)';

    public function handle(
        MetrikaReportingClient $client,
        MetrikaLeadEnricher $enricher,
    ): int {
        Config::set('metrika.reporting_log', true);

        if (! $client->isConfigured()) {
            $this->error('Metrika Reporting API не настроен.');
            $this->line('Нужны METRIKA_REPORTING_ENABLED=true и METRIKA_OAUTH_TOKEN в .env');
            $this->line('Логи: storage/logs/metrika.log');

            return self::FAILURE;
        }

        $lead = Lead::query()->with('site')->find($this->argument('lead'));

        if ($lead === null || $lead->site === null) {
            $this->error('Лид или проект не найден.');

            return self::FAILURE;
        }

        $clientId = trim((string) $lead->metrika_client_id);
        $counterId = trim((string) $lead->site->metrika_counter_id);

        $this->info('Лид: '.$lead->id);
        $this->line('Проект: '.$lead->site->name.' (счётчик '.$counterId.')');
        $this->line('Client ID: '.($clientId !== '' ? $clientId : '— не задан'));
        $this->line('Дата лида: '.($lead->created_at ?? now())->format('Y-m-d'));
        $this->line('Реклама до: '.($lead->advertising_channel ?? '—'));

        if ($clientId === '' || $counterId === '') {
            $this->error('У лида должен быть metrika_client_id, у проекта — metrika_counter_id.');

            return self::FAILURE;
        }

        $built = $client->buildRequestQuery(
            $counterId,
            $clientId,
            $lead->created_at ?? now(),
            $lead->site->timezone,
        );

        $this->newLine();
        $this->comment('Запрос (без OAuth-токена в URL):');
        $this->line($built['url']);

        if ($this->option('show-url')) {
            $this->newLine();
            $this->info('Скопируйте URL и проверьте в браузере/curl с заголовком Authorization: OAuth <token>');

            return self::SUCCESS;
        }

        $this->newLine();
        $this->comment('Вызов Reporting API…');

        $applied = $enricher->enrich($lead->id);

        $lead->refresh();

        $this->newLine();
        $this->info($applied ? 'Обогащение применено.' : 'Данных нет или обновлять нечего.');
        $this->line('Реклама после: '.($lead->advertising_channel ?? '—'));
        $this->line('utm_campaign_first: '.($lead->utm_campaign_first ?? '—'));
        $this->newLine();
        $this->comment('Подробный лог: storage/logs/metrika.log');
        $this->line('  docker compose exec app tail -50 storage/logs/metrika.log');

        return self::SUCCESS;
    }
}
