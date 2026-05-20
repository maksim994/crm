<?php

namespace App\Filament\Resources;

use App\Enums\AgencyClientStatus;
use App\Filament\Resources\AgencyClientResource\Pages;
use App\Filament\Resources\AgencyClientResource\RelationManagers\SitesRelationManager;
use App\Models\AgencyClient;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class AgencyClientResource extends Resource
{
    protected static ?string $model = AgencyClient::class;

    protected static ?string $navigationIcon = 'heroicon-o-building-office-2';

    protected static ?string $navigationLabel = 'Заказчики';

    protected static ?string $modelLabel = 'заказчик';

    protected static ?string $pluralModelLabel = 'Заказчики';

    protected static ?string $navigationGroup = 'CRM';

    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Основное')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->label('Название организации')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\TextInput::make('inn')
                            ->label('ИНН')
                            ->maxLength(12),
                        Forms\Components\Select::make('status')
                            ->label('Статус')
                            ->options(collect(AgencyClientStatus::cases())->mapWithKeys(
                                fn (AgencyClientStatus $s) => [$s->value => match ($s) {
                                    AgencyClientStatus::Active => 'Активен',
                                    AgencyClientStatus::Archived => 'В архиве',
                                }]
                            ))
                            ->required()
                            ->default(AgencyClientStatus::Active->value),
                    ])
                    ->columns(2),
                Forms\Components\Section::make('Контакты')
                    ->schema([
                        Forms\Components\TextInput::make('contact_name')
                            ->label('Контактное лицо'),
                        Forms\Components\TextInput::make('contact_email')
                            ->label('Email')
                            ->email(),
                        Forms\Components\TextInput::make('contact_phone')
                            ->label('Телефон')
                            ->tel(),
                    ])
                    ->columns(2),
                Forms\Components\Textarea::make('manager_comment')
                    ->label('Комментарий менеджера')
                    ->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Название')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('inn')
                    ->label('ИНН')
                    ->searchable(),
                Tables\Columns\TextColumn::make('contact_name')
                    ->label('Контакт'),
                Tables\Columns\TextColumn::make('sites_count')
                    ->label('Сайтов')
                    ->counts('sites'),
                Tables\Columns\TextColumn::make('status')
                    ->label('Статус')
                    ->badge()
                    ->formatStateUsing(fn (AgencyClientStatus $state) => match ($state) {
                        AgencyClientStatus::Active => 'Активен',
                        AgencyClientStatus::Archived => 'В архиве',
                    }),
                Tables\Columns\TextColumn::make('updated_at')
                    ->label('Обновлён')
                    ->dateTime('d.m.Y H:i')
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label('Статус')
                    ->options([
                        'active' => 'Активен',
                        'archived' => 'В архиве',
                    ]),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
            ])
            ->defaultSort('name');
    }

    public static function getRelations(): array
    {
        return [
            SitesRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListAgencyClients::route('/'),
            'create' => Pages\CreateAgencyClient::route('/create'),
            'view' => Pages\ViewAgencyClient::route('/{record}'),
            'edit' => Pages\EditAgencyClient::route('/{record}/edit'),
        ];
    }
}
