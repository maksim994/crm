<?php

namespace App\Filament\Resources\AgencyClientResource\RelationManagers;

use App\Filament\Schemas\SiteSchema;
use App\Filament\Support\SiteIntegration;
use App\Models\Site;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class SitesRelationManager extends RelationManager
{
    protected static string $relationship = 'sites';

    protected static ?string $title = 'Проекты (сайты)';

    protected static ?string $modelLabel = 'проект';

    protected static ?string $pluralModelLabel = 'Проекты';

    public function form(Form $form): Form
    {
        return $form
            ->schema(SiteSchema::formFields(includeClientSelect: false));
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('name')
            ->columns(SiteSchema::tableColumns(showClient: false))
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->label('Добавить проект')
                    ->mutateFormDataUsing(function (array $data): array {
                        $data['token_hash'] = '';

                        return $data;
                    })
                    ->after(function (Site $record): void {
                        $token = $record->issueToken();

                        Notification::make()
                            ->title('Проект создан — сохраните токен')
                            ->body("Токен:\n\n{$token}\n\n".SiteIntegration::instructions($record))
                            ->success()
                            ->persistent()
                            ->send();
                    }),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\Action::make('regenerateToken')
                    ->label('Токен')
                    ->icon('heroicon-o-key')
                    ->color('warning')
                    ->requiresConfirmation()
                    ->action(function (Site $record): void {
                        $token = $record->issueToken();

                        Notification::make()
                            ->title('Новый токен')
                            ->body($token)
                            ->warning()
                            ->persistent()
                            ->send();
                    }),
                Tables\Actions\DeleteAction::make(),
            ]);
    }
}
