<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Sezione/area dell'organigramma (es. "Direzione"): titolo + descrizione + ordine.
 * I membri vi appartengono tramite la stringa `org_chart_members.group` = titolo.
 */
class OrgChartSection extends Model
{
    protected $fillable = [
        'title', 'description', 'sort_order', 'is_active',
    ];

    protected function casts(): array
    {
        return ['is_active' => 'boolean', 'sort_order' => 'integer'];
    }
}
