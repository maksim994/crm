<?php

namespace App\Filament\Resources\SiteResource\Pages;

use App\Filament\Resources\SiteResource;
use App\Filament\Support\SiteIntegration;
use App\Models\Site;
use Filament\Actions;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;

class ViewSite extends ViewRecord
{
    protected static string $resource = SiteResource::class;

    protected static ?string $title = 'Проект';

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
            Actions\Action::make('regenerateToken')
                ->label('Перевыпустить токен')
                ->icon('heroicon-o-key')
                ->color('warning')
                ->requiresConfirmation()
                ->action(function (Site $record): void {
                    $token = $record->issueToken();

                    Notification::make()
                        ->title('Новый токен')
                        ->body($token."\n\n".SiteIntegration::instructions($record))
                        ->warning()
                        ->persistent()
                        ->send();
                }),
        ];
    }
}
