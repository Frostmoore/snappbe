<?php

namespace App\Notifications\Push\Transports;

use App\Notifications\Push\Contracts\AudiencePushTransport;
use App\Notifications\Push\PushMessage;
use App\Notifications\Push\PushResult;
use App\Services\Push\OneSignalClient;

/**
 * Transport push reale verso OneSignal (targeting per external id / segmento).
 */
class OneSignalTransport implements AudiencePushTransport
{
    public function __construct(private OneSignalClient $client) {}

    public function sendToExternalIds(array $externalIds, PushMessage $message): PushResult
    {
        $externalIds = array_values(array_unique(array_filter($externalIds, static fn ($id) => $id !== '' && $id !== null)));
        if ($externalIds === []) {
            return new PushResult();
        }

        return $this->client->send($message, [
            'include_aliases' => ['external_id' => $externalIds],
            'target_channel'  => 'push',
        ]);
    }

    public function sendToSegment(string $segment, PushMessage $message): PushResult
    {
        return $this->client->send($message, [
            'included_segments' => [$segment],
        ]);
    }
}
