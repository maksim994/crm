<?php

namespace App\Filament\Resources\SiteResource\Pages;

use App\Filament\Resources\SiteResource;
use App\Filament\Support\SiteIntegration;
use App\Models\Site;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;

class CreateSite extends CreateRecord
{
    protected static string $resource = SiteResource::class;

    protected static ?string $title = 'Новый проект';

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['token_hash'] = '';

        return $data;
    }

    protected function afterCreate(): void
    {
        /** @var Site $site */
        $site = $this->record;
        $token = $site->issueToken();

        Notification::make()
            ->title('Проект создан — сохраните токен')
            ->body("Токен (показывается один раз):\n\n{$token}\n\n".SiteIntegration::instructions($site))
            ->success()
            ->persistent()
            ->send();
    }

    protected function getRedirectUrl(): string
    {
        return SiteResource::getUrl('edit', ['record' => $this->record]);
    }
}
