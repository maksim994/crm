<?php

namespace App\Services;

use App\Exceptions\Client\MetrikaAnalyticsUnavailableException;
use App\Integrations\YandexMetrika\MetrikaBrandFilter;
use App\Integrations\YandexMetrika\MetrikaReportingClient;
use App\Models\Site;
use App\Support\DiagnosticCheck;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Schema;

class IntegrationDiagnosticsService
{
    public function __construct(
        private readonly MetrikaReportingClient $metrikaClient,
        private readonly InboundImapMailbox $imapMailbox,
    ) {}

    /**
     * @return array{status: string, checked_at: string, groups: list<array<string, mixed>>}
     */
    public function platform(): array
    {
        $groups = [
            $this->group('infrastructure', 'Инфраструктура', [
                $this->checkDatabase(),
                $this->checkRedis(),
                $this->checkStorage(),
                $this->checkAppKey(),
            ]),
            $this->group('metrika', 'Яндекс Метрика', [
                $this->checkMetrikaConfigured(),
                $this->checkMetrikaApi(),
                $this->checkMetrikaSitesCoverage(),
            ]),
            $this->group('email', 'Входящая почта', [
                $this->checkImapEnabled(),
                $this->checkImapCredentials(),
                $this->checkImapExtension(),
                $this->checkImapConnection(),
                $this->checkInboundWebhookSecret(),
            ]),
            $this->group('queue', 'Очередь и фон', [
                $this->checkQueueConnection(),
                $this->checkFailedJobs(),
                $this->checkSchedulerHint(),
            ]),
        ];

        return $this->buildResponse($groups);
    }

    /**
     * @return array{status: string, checked_at: string, site: array<string, string>, groups: list<array<string, mixed>>}
     */
    public function site(Site $site): array
    {
        $groups = [
            $this->group('project', 'Проект', [
                $this->checkSiteToken($site),
                $this->checkSiteInboundEmail($site),
            ]),
            $this->group('metrika', 'Яндекс Метрика', [
                $this->checkSiteMetrikaCounter($site),
                $this->checkSiteMetrikaBrandKeywords($site),
                $this->checkSiteMetrikaAccess($site),
            ]),
        ];

        $response = $this->buildResponse($groups);
        $response['site'] = [
            'id' => $site->id,
            'name' => $site->name,
        ];

        return $response;
    }

    /**
     * @param  list<DiagnosticCheck>  $checks
     * @return array<string, mixed>
     */
    private function group(string $id, string $title, array $checks): array
    {
        return [
            'id' => $id,
            'title' => $title,
            'checks' => array_map(static fn (DiagnosticCheck $check) => $check->toArray(), $checks),
        ];
    }

    /**
     * @param  list<array<string, mixed>>  $groups
     * @return array{status: string, checked_at: string, groups: list<array<string, mixed>>}
     */
    private function buildResponse(array $groups): array
    {
        $statuses = [];

        foreach ($groups as $group) {
            foreach ($group['checks'] as $check) {
                $statuses[] = $check['status'];
            }
        }

        $overall = DiagnosticCheck::STATUS_OK;

        if (in_array(DiagnosticCheck::STATUS_ERROR, $statuses, true)) {
            $overall = DiagnosticCheck::STATUS_ERROR;
        } elseif (in_array(DiagnosticCheck::STATUS_WARNING, $statuses, true)) {
            $overall = DiagnosticCheck::STATUS_WARNING;
        }

        return [
            'status' => $overall,
            'checked_at' => now()->toIso8601String(),
            'groups' => $groups,
        ];
    }

    private function checkDatabase(): DiagnosticCheck
    {
        try {
            DB::connection()->getPdo();

            return new DiagnosticCheck(
                'database',
                'PostgreSQL',
                DiagnosticCheck::STATUS_OK,
                'Подключение успешно',
            );
        } catch (\Throwable $exception) {
            return new DiagnosticCheck(
                'database',
                'PostgreSQL',
                DiagnosticCheck::STATUS_ERROR,
                'Не удалось подключиться к БД',
                $exception->getMessage(),
            );
        }
    }

    private function checkRedis(): DiagnosticCheck
    {
        try {
            Redis::ping();

            return new DiagnosticCheck(
                'redis',
                'Redis',
                DiagnosticCheck::STATUS_OK,
                'Ping успешен',
            );
        } catch (\Throwable $exception) {
            return new DiagnosticCheck(
                'redis',
                'Redis',
                DiagnosticCheck::STATUS_ERROR,
                'Redis недоступен',
                $exception->getMessage(),
            );
        }
    }

    private function checkStorage(): DiagnosticCheck
    {
        $paths = [
            storage_path('framework/sessions'),
            storage_path('logs'),
            storage_path('framework/cache'),
        ];

        foreach ($paths as $path) {
            if (! is_dir($path) || ! is_writable($path)) {
                return new DiagnosticCheck(
                    'storage',
                    'Storage',
                    DiagnosticCheck::STATUS_ERROR,
                    'Нет прав на запись: '.$path,
                    'Проверьте chown www-data для storage/ и bootstrap/cache',
                );
            }
        }

        return new DiagnosticCheck(
            'storage',
            'Storage',
            DiagnosticCheck::STATUS_OK,
            'Каталоги storage доступны для записи',
        );
    }

    private function checkAppKey(): DiagnosticCheck
    {
        try {
            app('encrypter')->encrypt('diagnostics-probe');

            return new DiagnosticCheck(
                'app_key',
                'APP_KEY',
                DiagnosticCheck::STATUS_OK,
                'Шифрование работает',
            );
        } catch (\Throwable) {
            return new DiagnosticCheck(
                'app_key',
                'APP_KEY',
                DiagnosticCheck::STATUS_ERROR,
                'APP_KEY отсутствует или некорректен',
                'Задайте APP_KEY=base64:... (php artisan key:generate --show)',
            );
        }
    }

    private function checkMetrikaConfigured(): DiagnosticCheck
    {
        if ($this->metrikaClient->isConfigured()) {
            return new DiagnosticCheck(
                'metrika_configured',
                'Reporting API',
                DiagnosticCheck::STATUS_OK,
                'METRIKA_REPORTING_ENABLED и OAuth-токен заданы',
            );
        }

        return new DiagnosticCheck(
            'metrika_configured',
            'Reporting API',
            DiagnosticCheck::STATUS_WARNING,
            'Reporting API не настроен',
            'Задайте METRIKA_REPORTING_ENABLED=true и METRIKA_OAUTH_TOKEN в .env',
        );
    }

    private function checkMetrikaApi(): DiagnosticCheck
    {
        if (! $this->metrikaClient->isConfigured()) {
            return new DiagnosticCheck(
                'metrika_api',
                'Доступ к API',
                DiagnosticCheck::STATUS_SKIPPED,
                'Пропущено — Reporting API не настроен',
            );
        }

        $site = Site::query()
            ->whereNotNull('metrika_counter_id')
            ->where('metrika_counter_id', '!=', '')
            ->orderBy('name')
            ->first();

        if ($site === null) {
            return new DiagnosticCheck(
                'metrika_api',
                'Доступ к API',
                DiagnosticCheck::STATUS_WARNING,
                'Нет проектов со счётчиком Метрики для проверки',
                'Укажите metrika_counter_id хотя бы в одном проекте',
            );
        }

        return $this->metrikaAccessCheck(
            'metrika_api',
            'Доступ к API',
            trim((string) $site->metrika_counter_id),
            $site->timezone,
            'Счётчик '.$site->metrika_counter_id.' ('.$site->name.')',
        );
    }

    private function checkMetrikaSitesCoverage(): DiagnosticCheck
    {
        $total = Site::query()->count();
        $withCounter = Site::query()
            ->whereNotNull('metrika_counter_id')
            ->where('metrika_counter_id', '!=', '')
            ->count();

        if ($total === 0) {
            return new DiagnosticCheck(
                'metrika_sites',
                'Счётчики в проектах',
                DiagnosticCheck::STATUS_SKIPPED,
                'Нет проектов',
            );
        }

        if ($withCounter === $total) {
            return new DiagnosticCheck(
                'metrika_sites',
                'Счётчики в проектах',
                DiagnosticCheck::STATUS_OK,
                "У всех проектов указан счётчик ({$withCounter}/{$total})",
            );
        }

        if ($withCounter === 0) {
            return new DiagnosticCheck(
                'metrika_sites',
                'Счётчики в проектах',
                DiagnosticCheck::STATUS_WARNING,
                'Ни у одного проекта не указан счётчик Метрики',
            );
        }

        return new DiagnosticCheck(
            'metrika_sites',
            'Счётчики в проектах',
            DiagnosticCheck::STATUS_WARNING,
            "Счётчик указан у {$withCounter} из {$total} проектов",
        );
    }

    private function checkImapEnabled(): DiagnosticCheck
    {
        if ((bool) config('crm.inbound_imap.enabled')) {
            return new DiagnosticCheck(
                'imap_enabled',
                'IMAP polling',
                DiagnosticCheck::STATUS_OK,
                'INBOUND_IMAP_ENABLED=true',
            );
        }

        return new DiagnosticCheck(
            'imap_enabled',
            'IMAP polling',
            DiagnosticCheck::STATUS_WARNING,
            'IMAP polling отключён',
            'Для приёма почты через ящик задайте INBOUND_IMAP_ENABLED=true',
        );
    }

    private function checkImapCredentials(): DiagnosticCheck
    {
        if (! (bool) config('crm.inbound_imap.enabled')) {
            return new DiagnosticCheck(
                'imap_credentials',
                'Учётные данные IMAP',
                DiagnosticCheck::STATUS_SKIPPED,
                'IMAP отключён',
            );
        }

        $host = filled(config('crm.inbound_imap.host'));
        $username = filled(config('crm.inbound_imap.username'));
        $password = filled(config('crm.inbound_imap.password'));

        if ($host && $username && $password) {
            return new DiagnosticCheck(
                'imap_credentials',
                'Учётные данные IMAP',
                DiagnosticCheck::STATUS_OK,
                'Host, username и password заданы',
            );
        }

        return new DiagnosticCheck(
            'imap_credentials',
            'Учётные данные IMAP',
            DiagnosticCheck::STATUS_ERROR,
            'Не заданы INBOUND_IMAP_HOST / USERNAME / PASSWORD',
        );
    }

    private function checkImapExtension(): DiagnosticCheck
    {
        if (! (bool) config('crm.inbound_imap.enabled')) {
            return new DiagnosticCheck(
                'imap_extension',
                'PHP ext-imap',
                DiagnosticCheck::STATUS_SKIPPED,
                'IMAP отключён',
            );
        }

        if (function_exists('imap_open')) {
            return new DiagnosticCheck(
                'imap_extension',
                'PHP ext-imap',
                DiagnosticCheck::STATUS_OK,
                'Расширение imap установлено',
            );
        }

        return new DiagnosticCheck(
            'imap_extension',
            'PHP ext-imap',
            DiagnosticCheck::STATUS_ERROR,
            'PHP ext-imap не установлен',
            'Установите ext-imap в Docker-образ (Dockerfile.prod)',
        );
    }

    private function checkImapConnection(): DiagnosticCheck
    {
        if (! (bool) config('crm.inbound_imap.enabled')) {
            return new DiagnosticCheck(
                'imap_connection',
                'Подключение к ящику',
                DiagnosticCheck::STATUS_SKIPPED,
                'IMAP отключён',
            );
        }

        $result = $this->imapMailbox->testConnection();

        if ($result['ok']) {
            return new DiagnosticCheck(
                'imap_connection',
                'Подключение к ящику',
                DiagnosticCheck::STATUS_OK,
                $result['message'],
            );
        }

        return new DiagnosticCheck(
            'imap_connection',
            'Подключение к ящику',
            DiagnosticCheck::STATUS_ERROR,
            $result['message'],
            $result['hint'] ?? null,
        );
    }

    private function checkInboundWebhookSecret(): DiagnosticCheck
    {
        if (filled(config('crm.inbound_webhook_secret'))) {
            return new DiagnosticCheck(
                'inbound_webhook',
                'Webhook секрет',
                DiagnosticCheck::STATUS_OK,
                'INBOUND_WEBHOOK_SECRET задан',
            );
        }

        if (app()->environment('production')) {
            return new DiagnosticCheck(
                'inbound_webhook',
                'Webhook секрет',
                DiagnosticCheck::STATUS_WARNING,
                'INBOUND_WEBHOOK_SECRET не задан',
                'POST /ingest/inbound-email без авторизации',
            );
        }

        return new DiagnosticCheck(
            'inbound_webhook',
            'Webhook секрет',
            DiagnosticCheck::STATUS_WARNING,
            'INBOUND_WEBHOOK_SECRET не задан (допустимо в dev)',
        );
    }

    private function checkQueueConnection(): DiagnosticCheck
    {
        $driver = (string) config('queue.default');

        if ($driver === 'sync') {
            return new DiagnosticCheck(
                'queue_driver',
                'Очередь',
                DiagnosticCheck::STATUS_WARNING,
                'QUEUE_CONNECTION=sync — jobs выполняются синхронно',
                'На production используйте redis + queue:work',
            );
        }

        return new DiagnosticCheck(
            'queue_driver',
            'Очередь',
            DiagnosticCheck::STATUS_OK,
            'Драйвер очереди: '.$driver,
        );
    }

    private function checkFailedJobs(): DiagnosticCheck
    {
        if (! Schema::hasTable('failed_jobs')) {
            return new DiagnosticCheck(
                'failed_jobs',
                'Failed jobs',
                DiagnosticCheck::STATUS_SKIPPED,
                'Таблица failed_jobs не найдена',
            );
        }

        $count = (int) DB::table('failed_jobs')->count();

        if ($count === 0) {
            return new DiagnosticCheck(
                'failed_jobs',
                'Failed jobs',
                DiagnosticCheck::STATUS_OK,
                'Нет упавших задач',
            );
        }

        return new DiagnosticCheck(
            'failed_jobs',
            'Failed jobs',
            DiagnosticCheck::STATUS_WARNING,
            "В очереди {$count} упавших задач",
            'Проверьте php artisan queue:failed',
        );
    }

    private function checkSchedulerHint(): DiagnosticCheck
    {
        $hints = ['Cron: * * * * * php artisan schedule:run'];

        if ((bool) config('crm.inbound_imap.enabled')) {
            $hints[] = 'mail:fetch-inbound каждые 5 мин при INBOUND_IMAP_ENABLED=true';
        }

        return new DiagnosticCheck(
            'scheduler',
            'Scheduler',
            DiagnosticCheck::STATUS_WARNING,
            'Проверьте вручную, что cron запущен',
            implode('; ', $hints),
        );
    }

    private function checkSiteToken(Site $site): DiagnosticCheck
    {
        if (filled($site->token_hash)) {
            return new DiagnosticCheck(
                'site_token',
                'Токен ingest',
                DiagnosticCheck::STATUS_OK,
                'Токен проекта выпущен',
            );
        }

        return new DiagnosticCheck(
            'site_token',
            'Токен ingest',
            DiagnosticCheck::STATUS_ERROR,
            'Токен не выпущен',
            'Перевыпустите токен на карточке проекта',
        );
    }

    private function checkSiteInboundEmail(Site $site): DiagnosticCheck
    {
        $addresses = $site->inboundEmailAddresses();

        if ($addresses !== []) {
            return new DiagnosticCheck(
                'site_inbound_email',
                'Адреса пересылки',
                DiagnosticCheck::STATUS_OK,
                implode(', ', $addresses),
            );
        }

        return new DiagnosticCheck(
            'site_inbound_email',
            'Адреса пересылки',
            DiagnosticCheck::STATUS_WARNING,
            'Не указан ни один inbound email',
            'Нужен для сопоставления входящей почты с проектом (основной, SEO или остальные)',
        );
    }

    private function checkSiteMetrikaCounter(Site $site): DiagnosticCheck
    {
        if (filled($site->metrika_counter_id)) {
            return new DiagnosticCheck(
                'site_metrika_counter',
                'Счётчик Метрики',
                DiagnosticCheck::STATUS_OK,
                (string) $site->metrika_counter_id,
            );
        }

        return new DiagnosticCheck(
            'site_metrika_counter',
            'Счётчик Метрики',
            DiagnosticCheck::STATUS_WARNING,
            'Счётчик не указан',
        );
    }

    private function checkSiteMetrikaBrandKeywords(Site $site): DiagnosticCheck
    {
        $keywords = MetrikaBrandFilter::keywords($site);

        if ($keywords !== []) {
            return new DiagnosticCheck(
                'site_metrika_brand',
                'Ключевые слова бренда',
                DiagnosticCheck::STATUS_OK,
                implode(', ', $keywords),
            );
        }

        return new DiagnosticCheck(
            'site_metrika_brand',
            'Ключевые слова бренда',
            DiagnosticCheck::STATUS_WARNING,
            'Не заданы',
            'Нужны для отчётов «брендовый / небрендовый поиск» в ЛК',
        );
    }

    private function checkSiteMetrikaAccess(Site $site): DiagnosticCheck
    {
        if (blank($site->metrika_counter_id)) {
            return new DiagnosticCheck(
                'site_metrika_api',
                'Доступ к счётчику',
                DiagnosticCheck::STATUS_SKIPPED,
                'Счётчик не указан',
            );
        }

        if (! $this->metrikaClient->isConfigured()) {
            return new DiagnosticCheck(
                'site_metrika_api',
                'Доступ к счётчику',
                DiagnosticCheck::STATUS_SKIPPED,
                'Reporting API не настроен на сервере',
            );
        }

        return $this->metrikaAccessCheck(
            'site_metrika_api',
            'Доступ к счётчику',
            trim((string) $site->metrika_counter_id),
            $site->timezone,
        );
    }

    private function metrikaAccessCheck(
        string $id,
        string $label,
        string $counterId,
        ?string $timezone,
        ?string $successSuffix = null,
    ): DiagnosticCheck {
        try {
            $from = Carbon::yesterday()->startOfDay();
            $to = Carbon::yesterday()->endOfDay();

            $this->metrikaClient->fetchReport(
                $counterId,
                ['ym:s:visits'],
                ['ym:s:trafficSource'],
                $from,
                $to,
                timezone: $timezone,
                limit: 1,
            );

            $message = 'API отвечает, доступ к счётчику есть';

            if ($successSuffix !== null) {
                $message .= ' — '.$successSuffix;
            }

            return new DiagnosticCheck(
                $id,
                $label,
                DiagnosticCheck::STATUS_OK,
                $message,
            );
        } catch (MetrikaAnalyticsUnavailableException $exception) {
            return new DiagnosticCheck(
                $id,
                $label,
                DiagnosticCheck::STATUS_ERROR,
                $exception->getMessage(),
            );
        } catch (\Throwable $exception) {
            return new DiagnosticCheck(
                $id,
                $label,
                DiagnosticCheck::STATUS_ERROR,
                'Ошибка запроса к Метрике',
                $exception->getMessage(),
            );
        }
    }
}
