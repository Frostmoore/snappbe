<?php

namespace App\Filament\Resources\PushNotificationResource\Pages;

use App\Filament\Resources\PushNotificationResource;
use App\Services\PushNotificationService;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;

class CreatePushNotification extends CreateRecord
{
    protected static string $resource = PushNotificationResource::class;

    /** Se true, dopo il salvataggio la notifica viene subito accodata per l'invio. */
    public bool $sendAfterCreate = false;

    protected function getFormActions(): array
    {
        return [
            $this->getCreateFormAction(),
            Action::make('saveAndSend')
                ->label('Salva e invia')
                ->icon('heroicon-o-paper-airplane')
                ->color('success')
                ->action(function () {
                    $this->sendAfterCreate = true;
                    $this->create();
                }),
            $this->getCancelFormAction(),
        ];
    }

    protected function afterCreate(): void
    {
        if (! $this->sendAfterCreate) {
            return;
        }

        app(PushNotificationService::class)->queue($this->record);

        Notification::make()
            ->title('Notifica salvata e in invio')
            ->body('L\'invio è in corso in background. Lo stato passerà a "Inviata" al termine.')
            ->success()
            ->send();
    }
}
