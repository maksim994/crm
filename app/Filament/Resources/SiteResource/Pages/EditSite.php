<?php

namespace App\Filament\Resources\SiteResource\Pages;

use App\Filament\Resources\SiteResource;
use App\Filament\Support\SiteIntegration;
use App\Models\Site;
use Filament\Actions;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;

class EditSite extends EditRecord
{
    protected static string $resource = SiteResource::class;

    protected static ?string $title = 'Редактирование проекта';

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
            Actions\Action::make('regenerateToken')
                ->label('Перевыпустить токен')
                ->icon('heroicon-o-key')
                ->color('warning')
                ->requiresConfirmation()
                ->modalHeading('Перевыпустить токен?')
                ->modalDescription('Старый токен перестанет работать.')
                ->action(function (Site $record): void {
                    $token = $record->issueToken();

                    Notification::make()
                        ->title('Новый токен')
                        ->body($token."\n\n".SiteIntegration::instructions($record))
                        ->warning()
                        ->persistent()
                        ->send();
                }),
            Actions\DeleteAction::make(),
        ];
    }
}
