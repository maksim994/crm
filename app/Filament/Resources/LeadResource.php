<?php

namespace App\Filament\Resources;

use App\Enums\LeadChannel;
use App\Enums\LeadStatus;
use App\Filament\Resources\LeadResource\Pages;
use App\Models\AgencyClient;
use App\Models\Lead;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class LeadResource extends Resource
{
    protected static ?string $model = Lead::class;

    protected static ?string $navigationIcon = 'heroicon-o-inbox';

    protected static ?string $navigationLabel = 'Лиды';

    protected static ?string $modelLabel = 'лид';

    protected static ?string $pluralModelLabel = 'Лиды';

    protected static ?string $navigationGroup = 'CRM';

    protected static ?int $navigationSort = 3;

    public static function canCreate(): bool
    {
        return false;
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->with(['site.agencyClient']);
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Обработка менеджером')
                    ->schema([
                        Forms\Components\Select::make('lead_status')
                            ->label('Статус лида')
                            ->options(self::leadStatusOptions())
                            ->required(),
                        Forms\Components\TextInput::make('manager_name')
                            ->label('Менеджер'),
                        Forms\Components\Textarea::make('manager_comment')
                            ->label('Комментарий менеджера')
                            ->columnSpanFull(),
                    ])
                    ->columns(2),
                Forms\Components\Section::make('АЦЦ / PPC')
                    ->schema([
                        Forms\Components\TextInput::make('acc_status')
                            ->label('Статус АЦЦ'),
                        Forms\Components\Textarea::make('acc_comment')
                            ->label('Комментарий АЦЦ')
                            ->columnSpanFull(),
                        Forms\Components\TextInput::make('ppc_status')
                            ->label('Статус PPC'),
                        Forms\Components\Textarea::make('ppc_comment')
                            ->label('Комментарий PPC')
                            ->columnSpanFull(),
                    ])
                    ->columns(2),
            ]);
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Infolists\Components\Section::make('Контекст')
                    ->schema([
                        Infolists\Components\TextEntry::make('created_at')
                            ->label('Создан')
                            ->dateTime('d.m.Y H:i'),
                        Infolists\Components\TextEntry::make('site.agencyClient.name')
                            ->label('Заказчик'),
                        Infolists\Components\TextEntry::make('site.name')
                            ->label('Сайт'),
                        Infolists\Components\TextEntry::make('channel')
                            ->label('Канал')
                            ->formatStateUsing(fn (LeadChannel $state) => $state->label()),
                        Infolists\Components\TextEntry::make('lead_status')
                            ->label('Статус')
                            ->formatStateUsing(fn (LeadStatus $state) => $state->label()),
                        Infolists\Components\IconEntry::make('is_duplicate')
                            ->label('Дубль')
                            ->boolean(),
                    ])
                    ->columns(3),
                Infolists\Components\Section::make('Контакт')
                    ->schema([
                        Infolists\Components\TextEntry::make('phone')->label('Телефон'),
                        Infolists\Components\TextEntry::make('email')->label('Email'),
                        Infolists\Components\TextEntry::make('contact_name')->label('Имя'),
                        Infolists\Components\TextEntry::make('form_description')->label('Форма'),
                        Infolists\Components\TextEntry::make('inn')->label('ИНН'),
                        Infolists\Components\TextEntry::make('city')->label('Город'),
                        Infolists\Components\TextEntry::make('product_request')->label('Запрос'),
                        Infolists\Components\TextEntry::make('comment')->label('Комментарий')->columnSpanFull(),
                        Infolists\Components\TextEntry::make('sku_count')->label('SKU'),
                        Infolists\Components\TextEntry::make('expected_amount')->label('Сумма'),
                    ])
                    ->columns(3),
                Infolists\Components\Section::make('Менеджер')
                    ->schema([
                        Infolists\Components\TextEntry::make('manager_name')->label('Менеджер'),
                        Infolists\Components\TextEntry::make('manager_comment')
                            ->label('Комментарий')
                            ->columnSpanFull(),
                    ]),
                Infolists\Components\Section::make('Аналитика')
                    ->schema([
                        Infolists\Components\TextEntry::make('metrika_client_id')->label('Client ID Метрики'),
                        Infolists\Components\TextEntry::make('utm_source')->label('utm_source'),
                        Infolists\Components\TextEntry::make('utm_medium')->label('utm_medium'),
                        Infolists\Components\TextEntry::make('utm_campaign')->label('utm_campaign'),
                        Infolists\Components\TextEntry::make('utm_term')->label('utm_term'),
                        Infolists\Components\TextEntry::make('utm_content')->label('utm_content'),
                        Infolists\Components\TextEntry::make('utm_campaign_first')->label('utm_campaign_first'),
                        Infolists\Components\TextEntry::make('advertising_channel')->label('Канал рекламы'),
                        Infolists\Components\TextEntry::make('landing_domain')->label('Домен'),
                        Infolists\Components\TextEntry::make('visitor_ip')->label('IP'),
                    ])
                    ->columns(3),
                Infolists\Components\Section::make('Звонок')
                    ->schema([
                        Infolists\Components\TextEntry::make('call_recording_url')
                            ->label('Запись')
                            ->url(fn (?string $state) => $state),
                        Infolists\Components\TextEntry::make('call_duration_sec')->label('Длительность, сек'),
                    ])
                    ->columns(2)
                    ->visible(fn (Lead $record) => $record->channel === LeadChannel::Call),
                Infolists\Components\Section::make('АЦЦ / PPC')
                    ->schema([
                        Infolists\Components\TextEntry::make('acc_status')->label('АЦЦ'),
                        Infolists\Components\TextEntry::make('acc_comment')->label('Комментарий АЦЦ'),
                        Infolists\Components\TextEntry::make('ppc_status')->label('PPC'),
                        Infolists\Components\TextEntry::make('ppc_comment')->label('Комментарий PPC'),
                        Infolists\Components\TextEntry::make('acc_ppc_summary')->label('Сводка'),
                    ])
                    ->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Дата')
                    ->dateTime('d.m.Y H:i')
                    ->sortable(),
                Tables\Columns\TextColumn::make('site.agencyClient.name')
                    ->label('Заказчик')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('site.name')
                    ->label('Сайт')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('channel')
                    ->label('Канал')
                    ->formatStateUsing(fn (LeadChannel $state) => $state->label())
                    ->badge(),
                Tables\Columns\TextColumn::make('phone')
                    ->label('Телефон')
                    ->searchable(),
                Tables\Columns\TextColumn::make('contact_name')
                    ->label('Имя')
                    ->searchable(),
                Tables\Columns\TextColumn::make('lead_status')
                    ->label('Статус')
                    ->formatStateUsing(fn (LeadStatus $state) => $state->label())
                    ->badge(),
                Tables\Columns\IconColumn::make('is_duplicate')
                    ->label('Дубль')
                    ->boolean(),
            ])
            ->filters([
                Tables\Filters\Filter::make('agency_client')
                    ->label('Заказчик')
                    ->form([
                        Forms\Components\Select::make('agency_client_id')
                            ->label('Заказчик')
                            ->options(fn () => AgencyClient::query()->orderBy('name')->pluck('name', 'id'))
                            ->searchable(),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query->when(
                            $data['agency_client_id'] ?? null,
                            fn (Builder $q, string $clientId) => $q->whereHas(
                                'site',
                                fn (Builder $siteQuery) => $siteQuery->where('agency_client_id', $clientId)
                            )
                        );
                    }),
                Tables\Filters\SelectFilter::make('site_id')
                    ->label('Сайт')
                    ->relationship('site', 'name')
                    ->searchable()
                    ->preload(),
                Tables\Filters\SelectFilter::make('channel')
                    ->label('Канал')
                    ->options(collect(LeadChannel::cases())->mapWithKeys(
                        fn (LeadChannel $c) => [$c->value => $c->label()]
                    )),
                Tables\Filters\SelectFilter::make('lead_status')
                    ->label('Статус')
                    ->options(self::leadStatusOptions()),
                Tables\Filters\Filter::make('created_at')
                    ->label('Период')
                    ->form([
                        Forms\Components\DatePicker::make('from')->label('С'),
                        Forms\Components\DatePicker::make('until')->label('По'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when($data['from'] ?? null, fn (Builder $q, $date) => $q->whereDate('created_at', '>=', $date))
                            ->when($data['until'] ?? null, fn (Builder $q, $date) => $q->whereDate('created_at', '<=', $date));
                    }),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListLeads::route('/'),
            'view' => Pages\ViewLead::route('/{record}'),
            'edit' => Pages\EditLead::route('/{record}/edit'),
        ];
    }

    /**
     * @return array<string, string>
     */
    private static function leadStatusOptions(): array
    {
        return collect(LeadStatus::cases())
            ->mapWithKeys(fn (LeadStatus $s) => [$s->value => $s->label()])
            ->all();
    }
}
