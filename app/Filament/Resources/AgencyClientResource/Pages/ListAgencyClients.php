<?php

namespace App\Filament\Resources\AgencyClientResource\Pages;

use App\Filament\Resources\AgencyClientResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListAgencyClients extends ListRecords
{
    protected static string $resource = AgencyClientResource::class;

    protected static ?string $title = 'Заказчики';

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('Добавить заказчика'),
        ];
    }
}
