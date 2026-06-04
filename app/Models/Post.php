<?php

namespace App\Models;

use App\Enums\PostStatus;
use App\Enums\PostType;
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
        'external_url',
    ];

    protected function casts(): array
    {
        return [
            'status' => PostStatus::class,
            'type' => PostType::class,
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
}
