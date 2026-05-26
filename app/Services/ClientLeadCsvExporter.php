<?php

namespace App\Services;

use App\Models\Lead;
use Illuminate\Database\Eloquent\Builder;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ClientLeadCsvExporter
{
    /** @var list<string> */
    private const HEADERS = [
        'Дата',
        'Проект',
        'Телефон',
        'Email',
        'Источник',
        'Рекламный канал',
        'Описание формы',
        'Статус',
        'ФИО',
        'Город',
        'Запрос на продукт',
        'Комментарий',
        'Кол-во SKU',
        'Домен посадочной',
        'UTM Source',
        'UTM Medium',
        'UTM Campaign',
        'UTM Term',
        'UTM Content',
        'UTM Campaign (первая)',
        'Client ID Метрики',
        'ID лида',
        'Запись звонка',
        'Длительность звонка (сек)',
    ];

    public function download(Builder $query, string $filename = 'leads.csv'): StreamedResponse
    {
        return response()->streamDownload(function () use ($query) {
            $handle = fopen('php://output', 'w');
            fprintf($handle, chr(0xEF).chr(0xBB).chr(0xBF));
            fputcsv($handle, self::HEADERS, ';');

            $query->clone()->chunk(200, function ($leads) use ($handle) {
                foreach ($leads as $lead) {
                    /** @var Lead $lead */
                    fputcsv($handle, $this->row($lead), ';');
                }
            });

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    /** @return list<string|null> */
    private function row(Lead $lead): array
    {
        $lead->loadMissing('site');

        return [
            $lead->created_at?->timezone($lead->site?->timezone ?? 'Europe/Moscow')->format('Y-m-d H:i:s'),
            $lead->site?->name,
            $lead->phone,
            $lead->email,
            $lead->channel->label(),
            $lead->advertising_channel,
            $lead->form_description,
            $lead->lead_status->label(),
            $lead->contact_name,
            $lead->city,
            $lead->product_request,
            $lead->comment,
            $lead->sku_count !== null ? (string) $lead->sku_count : null,
            $lead->landing_domain,
            $lead->utm_source,
            $lead->utm_medium,
            $lead->utm_campaign,
            $lead->utm_term,
            $lead->utm_content,
            $lead->utm_campaign_first,
            $lead->metrika_client_id,
            $lead->id,
            $lead->call_recording_url,
            $lead->call_duration_sec !== null ? (string) $lead->call_duration_sec : null,
        ];
    }
}
