<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SiteResource\Pages;
use App\Filament\Schemas\SiteSchema;
use App\Filament\Support\SiteIntegration;
use App\Enums\SiteStatus;
use App\Models\Site;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class SiteResource extends Resource
{
    protected static ?string $model = Site::class;

    protected static ?string $navigationIcon = 'heroicon-o-globe-alt';

    protected static ?string $navigationLabel = 'Проекты';

    protected static ?string $modelLabel = 'проект';

    protected static ?string $pluralModelLabel = 'Проекты';

    protected static ?string $navigationGroup = 'CRM';

    protected static ?int $navigationSort = 2;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Проект')
                    ->description('Сайт или лендинг, с которого принимаются лиды')
                    ->schema(SiteSchema::formFields())
                    ->columns(2),
            ]);
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Infolists\Components\Section::make('Проект')
                    ->schema([
                        Infolists\Components\TextEntry::make('agencyClient.name')->label('Заказчик'),
                        Infolists\Components\TextEntry::make('name')->label('Название'),
                        Infolists\Components\TextEntry::make('domains')->label('Домены')->badge(),
                        Infolists\Components\TextEntry::make('metrika_counter_id')->label('Метрика'),
                        Infolists\Components\TextEntry::make('metrika_brand_keywords')
                            ->label('Ключевые слова бренда')
                            ->formatStateUsing(fn (?array $state) => $state ? implode(', ', $state) : '—'),
                        Infolists\Components\TextEntry::make('timezone')->label('Часовой пояс'),
                        Infolists\Components\TextEntry::make('status')
                            ->label('Статус')
                            ->formatStateUsing(fn (SiteStatus $state) => match ($state) {
                                SiteStatus::Active => 'Активен',
                                SiteStatus::Paused => 'Приостановлен',
                                SiteStatus::Archived => 'В архиве',
                            }),
                        Infolists\Components\TextEntry::make('email_inbound_address')->label('Inbound email'),
                    ])
                    ->columns(2),
                Infolists\Components\Section::make('Интеграция')
                    ->schema([
                        Infolists\Components\TextEntry::make('integration')
                            ->label('Инструкция')
                            ->state(fn (Site $record) => SiteIntegration::instructions($record))
                            ->markdown(false)
                            ->columnSpanFull(),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns(SiteSchema::tableColumns())
            ->filters([
                Tables\Filters\SelectFilter::make('agency_client_id')
                    ->label('Заказчик')
                    ->relationship('agencyClient', 'name')
                    ->searchable()
                    ->preload(),
                SiteSchema::statusFilter(),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\Action::make('regenerateToken')
                    ->label('Токен')
                    ->icon('heroicon-o-key')
                    ->color('warning')
                    ->requiresConfirmation()
                    ->modalHeading('Перевыпустить токен?')
                    ->modalDescription('Старый токен перестанет работать на формах и в интеграциях.')
                    ->action(function (Site $record): void {
                        $token = $record->issueToken();

                        Notification::make()
                            ->title('Новый токен проекта')
                            ->body($token)
                            ->warning()
                            ->persistent()
                            ->send();
                    }),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->label('Добавить проект'),
            ])
            ->emptyStateHeading('Нет проектов')
            ->emptyStateDescription('Создайте первый проект (сайт) для приёма лидов.')
            ->emptyStateActions([
                Tables\Actions\CreateAction::make()
                    ->label('Добавить проект'),
            ])
            ->defaultSort('name');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListSites::route('/'),
            'create' => Pages\CreateSite::route('/create'),
            'view' => Pages\ViewSite::route('/{record}'),
            'edit' => Pages\EditSite::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->withCount('leads');
    }
}
