<?php

namespace App\Filament\Resources\AgencyClientResource\Pages;

use App\Filament\Resources\AgencyClientResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewAgencyClient extends ViewRecord
{
    protected static string $resource = AgencyClientResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
        ];
    }
}
