<?php

namespace App\Filament\Resources\PushNotificationResource\Pages;

use App\Filament\Resources\PushNotificationResource;
use App\Services\PushNotificationService;
use Filament\Actions;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;

class EditPushNotification extends EditRecord
{
    protected static string $resource = PushNotificationResource::class;

    /** Se true, dopo il salvataggio la notifica viene subito accodata per l'invio. */
    public bool $sendAfterSave = false;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }

    protected function getFormActions(): array
    {
        return [
            $this->getSaveFormAction(),
            Action::make('saveAndSend')
                ->label('Salva e invia')
                ->icon('heroicon-o-paper-airplane')
                ->color('success')
                ->action(function () {
                    $this->sendAfterSave = true;
                    $this->save();
                }),
            $this->getCancelFormAction(),
        ];
    }

    protected function afterSave(): void
    {
        if (! $this->sendAfterSave) {
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
