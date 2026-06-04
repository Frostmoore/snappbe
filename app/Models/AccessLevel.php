<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Livello di accesso gerarchico. `weight` definisce l'ordine: un contenuto con
 * min_level = X è visibile a chi ha un livello con weight >= weight(X).
 * I `key` sono agnostici e verranno mappati ai livelli reali di WordPress (D4).
 */
class AccessLevel extends Model
{
    protected $fillable = ['key', 'label', 'weight'];

    protected function casts(): array
    {
        return ['weight' => 'integer'];
    }

    /** Peso del livello dato il suo key; 0 se sconosciuto/pubblico. */
    public static function weightFor(?string $key): int
    {
        if ($key === null) {
            return 0;
        }

        return (int) static::query()->where('key', $key)->value('weight');
    }
}
