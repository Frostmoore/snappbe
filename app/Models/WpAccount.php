<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Snapshot di un account del sito WordPress (incl. livello normalizzato).
 * Separato dalla pivot `account_links` per non duplicare i dati WP su ogni link.
 */
class WpAccount extends Model
{
    protected $fillable = [
        'wp_user_id',
        'username',
        'email',
        'level',
        'level_label',
        'roles',
        'role',
        'role_label',
        'synced_at',
    ];

    protected function casts(): array
    {
        return [
            'roles' => 'array',
            'synced_at' => 'datetime',
        ];
    }

    public function links(): HasMany
    {
        return $this->hasMany(AccountLink::class);
    }
}
