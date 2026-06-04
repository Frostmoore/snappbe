<?php

namespace App\Models;

use App\Enums\PushTarget;
use App\Notifications\Push\PushMessage;
use Illuminate\Database\Eloquent\Model;

/**
 * Notifica push composta (storico + composer Filament per l'invio manuale).
 * Sa convertirsi in un PushMessage universale da passare al service.
 */
class PushNotification extends Model
{
    protected $fillable = [
        'title',
        'body',
        'image_url',
        'image_path',
        'deep_link',
        'data',
        'target',
        'target_level',
        'target_user_ids',
        'status',
        'sent_at',
        'stats',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'data' => 'array',
            'target' => PushTarget::class,
            'target_user_ids' => 'array',
            'sent_at' => 'datetime',
            'stats' => 'array',
        ];
    }

    /** URL immagine: file caricato se presente, altrimenti URL esterno. */
    public function resolvedImageUrl(): ?string
    {
        return $this->image_path
            ? \Illuminate\Support\Facades\Storage::disk('public')->url($this->image_path)
            : $this->image_url;
    }

    public function toPushMessage(): PushMessage
    {
        return new PushMessage(
            title: $this->title,
            body: $this->body,
            image: $this->resolvedImageUrl(),
            deepLink: $this->deep_link,
            data: $this->data ?? [],
        );
    }
}
