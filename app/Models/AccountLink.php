<?php

namespace App\Models;

use App\Enums\AccountLinkStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Pivot tra utente app e account WordPress (la "tabella pivot" richiesta).
 */
class AccountLink extends Model
{
    protected $fillable = [
        'user_id',
        'wp_account_id',
        'status',
        'verification_method',
        'linked_at',
    ];

    protected function casts(): array
    {
        return [
            'status' => AccountLinkStatus::class,
            'linked_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function wpAccount(): BelongsTo
    {
        return $this->belongsTo(WpAccount::class);
    }
}
