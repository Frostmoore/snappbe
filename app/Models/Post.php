<?php

namespace App\Models;

use App\Enums\PostStatus;
use App\Enums\PostType;
use App\Enums\PushTarget;
use App\Services\Access\LevelGate;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Contenuto NATIVO in-app (non gli articoli WP, che restano in proxy).
 * La visibilità è per livello: min_level = key di access_levels (NULL = pubblico).
 */
class Post extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'author_id',
        'type',
        'title',
        'slug',
        'excerpt',
        'body',
        'cover_url',
        'cover_path',
        'status',
        'published_at',
        'min_level',
        'audience',
        'audience_role',
        'audience_user_ids',
        'external_url',
    ];

    protected function casts(): array
    {
        return [
            'status' => PostStatus::class,
            'type' => PostType::class,
            'audience' => PushTarget::class,
            'audience_user_ids' => 'array',
            'published_at' => 'datetime',
        ];
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'author_id');
    }

    /** URL della copertina: immagine caricata se presente, altrimenti URL esterno. */
    public function coverImageUrl(): ?string
    {
        return $this->cover_path
            ? \Illuminate\Support\Facades\Storage::disk('public')->url($this->cover_path)
            : $this->cover_url;
    }

    /** Solo i post pubblicati con data di pubblicazione passata. */
    public function scopePublished(Builder $query): Builder
    {
        return $query->where('status', PostStatus::Published->value)
            ->where(function (Builder $q) {
                $q->whereNull('published_at')->orWhere('published_at', '<=', now());
            });
    }

    /**
     * Filtra i post visibili all'utente in base al DESTINATARIO (audience):
     * tutti / livello (>=) / ruolo WP esatto / utenti specifici. Anonimo = solo "tutti".
     */
    public function scopeVisibleTo(Builder $query, ?User $user): Builder
    {
        return $query->where(function (Builder $q) use ($user) {
            // Pubblico per tutti.
            $q->where('audience', PushTarget::All->value);

            if (! $user) {
                return; // anonimo: solo i post per "tutti"
            }

            // Per livello: questo livello e superiori.
            $userWeight = $user->membership_level ? AccessLevel::weightFor($user->membership_level) : 0;
            $allowedKeys = AccessLevel::query()->where('weight', '<=', $userWeight)->pluck('key');
            $q->orWhere(fn (Builder $w) => $w
                ->where('audience', PushTarget::Level->value)
                ->where(fn (Builder $l) => $l
                    ->whereNull('min_level')->orWhere('min_level', 'public')->orWhereIn('min_level', $allowedKeys)
                )
            );

            // Per ruolo WP esatto.
            if ($user->wp_role) {
                $q->orWhere(fn (Builder $w) => $w
                    ->where('audience', PushTarget::Role->value)->where('audience_role', $user->wp_role)
                );
            }

            // Utenti specifici.
            $q->orWhere(fn (Builder $w) => $w
                ->where('audience', PushTarget::Users->value)->whereJsonContains('audience_user_ids', $user->id)
            );
        });
    }

    /** Verità singola sulla visibilità di QUESTO post per un utente (usata nel dettaglio). */
    public function isVisibleTo(?User $user): bool
    {
        return match ($this->audience) {
            PushTarget::All   => true,
            PushTarget::Level => app(LevelGate::class)->canSee($user, $this->min_level),
            PushTarget::Role  => $user !== null && $user->wp_role !== null && $user->wp_role === $this->audience_role,
            PushTarget::Users => $user !== null && in_array($user->id, $this->audience_user_ids ?? []),
        };
    }
}
