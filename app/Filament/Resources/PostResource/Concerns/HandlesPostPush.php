<?php

namespace App\Filament\Resources\PostResource\Concerns;

use App\Models\Post;
use App\Models\PushNotification;
use App\Services\PushNotificationService;
use Filament\Notifications\Notification;
use Illuminate\Support\Str;

/**
 * Gestisce l'invio (opzionale) di una notifica push al salvataggio di un post.
 *
 * I campi del form `send_push`, `push_target*` NON sono colonne del post: vanno
 * estratti dai dati prima del salvataggio e usati dopo (afterCreate/afterSave).
 */
trait HandlesPostPush
{
    /** @var array<string,mixed> */
    public array $pushConfig = [];

    /**
     * Estrae i campi della notifica dai dati del form (così non finiscono nel post).
     *
     * @param  array<string,mixed>  $data
     * @return array<string,mixed>
     */
    protected function extractPushConfig(array $data): array
    {
        foreach (['send_push', 'push_target', 'push_target_level', 'push_target_role', 'push_target_user_ids'] as $key) {
            if (array_key_exists($key, $data)) {
                $this->pushConfig[$key] = $data[$key];
                unset($data[$key]);
            }
        }

        return $data;
    }

    /** Se richiesto, compone e accoda la notifica push per il post salvato. */
    protected function maybeSendPostPush(Post $post): void
    {
        if (empty($this->pushConfig['send_push'])) {
            return;
        }

        $body = trim(strip_tags((string) $post->excerpt));
        if ($body === '') {
            $body = 'Nuova comunicazione SNA';
        }

        $notification = PushNotification::create([
            'title'           => $post->title,
            'body'            => Str::limit($body, 140),
            'deep_link'       => '/posts/'.$post->id,
            'target'          => $this->pushConfig['push_target'] ?? 'all',
            'target_level'    => $this->pushConfig['push_target_level'] ?? null,
            'target_role'     => $this->pushConfig['push_target_role'] ?? null,
            'target_user_ids' => $this->pushConfig['push_target_user_ids'] ?? null,
            'status'          => 'draft',
            'created_by'      => auth()->id(),
        ]);

        app(PushNotificationService::class)->queue($notification);

        Notification::make()
            ->title('Notifica in invio')
            ->body('La comunicazione è stata salvata e la notifica è in coda.')
            ->success()
            ->send();
    }
}
