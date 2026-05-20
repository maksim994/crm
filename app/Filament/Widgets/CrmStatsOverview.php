<?php

namespace App\Filament\Widgets;

use App\Models\AgencyClient;
use App\Models\Lead;
use App\Models\Site;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class CrmStatsOverview extends BaseWidget
{
    protected static ?int $sort = 0;

    protected function getStats(): array
    {
        return [
            Stat::make('Заказчики', (string) AgencyClient::query()->count())
                ->description('Организации в CRM')
                ->icon('heroicon-o-building-office-2')
                ->color('primary'),
            Stat::make('Проекты', (string) Site::query()->count())
                ->description('Сайты и лендинги')
                ->icon('heroicon-o-globe-alt')
                ->color('success'),
            Stat::make('Лиды', (string) Lead::query()->count())
                ->description('Всего в системе')
                ->icon('heroicon-o-inbox')
                ->color('warning'),
        ];
    }
}
