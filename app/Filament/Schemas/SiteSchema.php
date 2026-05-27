<?php

namespace App\Filament\Schemas;

use App\Enums\SiteStatus;
use App\Filament\Support\SiteIntegration;
use App\Models\Site;
use Filament\Forms;
use Filament\Forms\Components\Component;
use Filament\Tables;
use Filament\Tables\Columns\Column;

class SiteSchema
{
    /**
     * @return array<int, Component>
     */
    public static function formFields(bool $includeClientSelect = true, bool $includeIntegration = true): array
    {
        $fields = [];

        if ($includeClientSelect) {
            $fields[] = Forms\Components\Select::make('agency_client_id')
                ->label('Заказчик')
                ->relationship('agencyClient', 'name')
                ->searchable()
                ->preload()
                ->required();
        }

        $fields = array_merge($fields, [
            Forms\Components\TextInput::make('name')
                ->label('Название проекта')
                ->required()
                ->maxLength(255)
                ->columnSpanFull(),
            Forms\Components\TagsInput::make('domains')
                ->label('Домены')
                ->required()
                ->placeholder('example.ru'),
            Forms\Components\TextInput::make('metrika_counter_id')
                ->label('ID счётчика Метрики')
                ->maxLength(32),
            Forms\Components\TagsInput::make('metrika_brand_keywords')
                ->label('Ключевые слова бренда')
                ->placeholder('ruflex, руфлекс')
                ->helperText('Для отчётов «брендовый / небрендовый поиск» в ЛК. Фильтр по ym:s:searchPhrase.')
                ->columnSpanFull(),
            Forms\Components\Select::make('timezone')
                ->label('Часовой пояс')
                ->options([
                    'Europe/Moscow' => 'Europe/Moscow',
                    'Europe/Kaliningrad' => 'Europe/Kaliningrad',
                    'Asia/Yekaterinburg' => 'Asia/Yekaterinburg',
                    'Asia/Novosibirsk' => 'Asia/Novosibirsk',
                    'Asia/Vladivostok' => 'Asia/Vladivostok',
                ])
                ->default('Europe/Moscow')
                ->required(),
            Forms\Components\Select::make('status')
                ->label('Статус')
                ->options([
                    SiteStatus::Active->value => 'Активен',
                    SiteStatus::Paused->value => 'Приостановлен',
                    SiteStatus::Archived->value => 'В архиве',
                ])
                ->default(SiteStatus::Active->value)
                ->required(),
            Forms\Components\TextInput::make('email_inbound_address')
                ->label('Почта (реклама)')
                ->email()
                ->maxLength(255)
                ->columnSpanFull(),
            Forms\Components\TextInput::make('email_inbound_seo')
                ->label('Почта (SEO / поиск)')
                ->email()
                ->maxLength(255)
                ->columnSpanFull(),
            Forms\Components\TextInput::make('email_inbound_other')
                ->label('Почта (прямые заходы)')
                ->email()
                ->maxLength(255)
                ->columnSpanFull(),
        ]);

        if ($includeIntegration) {
            $fields[] = Forms\Components\Placeholder::make('integration_help')
                ->label('Инструкция по интеграции')
                ->content(fn (?Site $record) => $record
                    ? SiteIntegration::instructions($record)
                    : 'После сохранения проекта будет сгенерирован токен для приёма лидов.')
                ->columnSpanFull();
        }

        return $fields;
    }

    /**
     * @return array<int, Column>
     */
    public static function tableColumns(bool $showClient = true): array
    {
        $columns = [];

        if ($showClient) {
            $columns[] = Tables\Columns\TextColumn::make('agencyClient.name')
                ->label('Заказчик')
                ->sortable()
                ->searchable();
        }

        return array_merge($columns, [
            Tables\Columns\TextColumn::make('name')
                ->label('Проект')
                ->searchable()
                ->sortable(),
            Tables\Columns\TextColumn::make('domains')
                ->label('Домены')
                ->badge(),
            Tables\Columns\TextColumn::make('metrika_counter_id')
                ->label('Метрика')
                ->toggleable(),
            Tables\Columns\TextColumn::make('status')
                ->label('Статус')
                ->badge()
                ->formatStateUsing(fn (SiteStatus $state) => match ($state) {
                    SiteStatus::Active => 'Активен',
                    SiteStatus::Paused => 'Пауза',
                    SiteStatus::Archived => 'Архив',
                }),
            Tables\Columns\TextColumn::make('leads_count')
                ->label('Лидов')
                ->counts('leads')
                ->sortable(),
            Tables\Columns\TextColumn::make('updated_at')
                ->label('Обновлён')
                ->dateTime('d.m.Y H:i')
                ->sortable()
                ->toggleable(isToggledHiddenByDefault: true),
        ]);
    }

    public static function statusFilter(): Tables\Filters\SelectFilter
    {
        return Tables\Filters\SelectFilter::make('status')
            ->label('Статус')
            ->options([
                SiteStatus::Active->value => 'Активен',
                SiteStatus::Paused->value => 'Приостановлен',
                SiteStatus::Archived->value => 'В архиве',
            ]);
    }
}
