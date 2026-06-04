<?php

namespace App\Jobs;

use App\Models\PushNotification;
use App\Services\PushNotificationService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

/**
 * Invio asincrono di una notifica composta. Usato quando si vuole accodare
 * l'invio (es. audience grandi o sorgenti automatiche). Il pannello può anche
 * inviare in modo sincrono via PushNotificationService::sendComposed.
 */
class SendPushNotification implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public int $notificationId) {}

    public function handle(PushNotificationService $service): void
    {
        $notification = PushNotification::find($this->notificationId);

        if ($notification && $notification->status !== 'sent') {
            $service->sendComposed($notification);
        }
    }
}
