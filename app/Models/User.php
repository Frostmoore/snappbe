<?php

namespace App\Models;

use App\Enums\UserRole;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable implements FilamentUser, MustVerifyEmail
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'membership_level',
        'wp_role',
        'wp_role_label',
        'membership_synced_at',
        'sna_link_prompted_at',
        'provider',
        'provider_id',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
        'provider_id',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'membership_synced_at' => 'datetime',
            'sna_link_prompted_at' => 'datetime',
            'password' => 'hashed',
            'role' => UserRole::class,
        ];
    }

    public function accountLink(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(AccountLink::class);
    }

    /** Invia la verifica email tramite notifica accodata (non bloccante). */
    public function sendEmailVerificationNotification(): void
    {
        $this->notify(new \App\Notifications\Auth\QueuedVerifyEmail());
    }

    /** Invia il reset password tramite notifica accodata. */
    public function sendPasswordResetNotification($token): void
    {
        $this->notify(new \App\Notifications\Auth\QueuedResetPassword($token));
    }

    public function isSuperAdmin(): bool
    {
        return $this->role === UserRole::SuperAdmin;
    }

    public function isAdmin(): bool
    {
        return $this->role->isAdminOrAbove();
    }

    public function canAccessAdminPanel(): bool
    {
        return $this->role->isStaffOrAbove();
    }

    /** Filament: solo staff e superiori possono entrare nel pannello /admin. */
    public function canAccessPanel(Panel $panel): bool
    {
        return $this->canAccessAdminPanel();
    }
}
