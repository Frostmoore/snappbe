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
        // Solo il toggle: i destinatari sono colonne del post (audience*).
        if (array_key_exists('send_push', $data)) {
            $this->pushConfig['send_push'] = $data['send_push'];
            unset($data['send_push']);
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

        // La notifica colpisce ESATTAMENTE i destinatari del post (audience).
        $notification = PushNotification::create([
            'title'           => $post->title,
            'body'            => Str::limit($body, 140),
            'image_url'       => $post->coverImageUrl(), // immagine = copertina del post
            'deep_link'       => '/posts/'.$post->id,
            'target'          => $post->audience->value,
            'target_level'    => $post->min_level,
            'target_role'     => $post->audience_role,
            'target_user_ids' => $post->audience_user_ids,
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
