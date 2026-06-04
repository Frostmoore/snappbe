<?php

namespace App\Notifications\Push\Transports;

use App\Notifications\Push\Contracts\PushTransport;
use App\Notifications\Push\PushMessage;
use App\Notifications\Push\PushResult;
use Kreait\Firebase\Contract\Messaging;
use Kreait\Firebase\Messaging\CloudMessage;
use Kreait\Firebase\Messaging\Notification;

/**
 * Transport reale verso Firebase Cloud Messaging (HTTP v1, via kreait).
 * Risolto dal container SOLO quando le credenziali Firebase sono configurate
 * (vedi binding in AppServiceProvider).
 */
class FcmTransport implements PushTransport
{
    public function __construct(private Messaging $messaging) {}

    public function send(array $tokens, PushMessage $message): PushResult
    {
        if ($tokens === []) {
            return new PushResult();
        }

        $cloud = CloudMessage::new()
            ->withNotification(Notification::create($message->title, $message->body, $message->image))
            ->withData($message->dataPayload());

        if ($message->sound !== null) {
            $cloud = $cloud->withDefaultSounds();
        }

        $report = $this->messaging->sendMulticast($cloud, $tokens);

        return new PushResult(
            success: $report->successes()->count(),
            failure: $report->failures()->count(),
            invalidTokens: $report->invalidTokens(),
        );
    }
}
