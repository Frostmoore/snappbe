<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

class ChangePasswordRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // l'accesso è già protetto da auth:sanctum sulla rotta
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            // 'current_password' verifica la password attuale dell'utente loggato.
            'current_password' => ['required', 'string', 'current_password'],
            'password' => ['required', 'confirmed', 'different:current_password', Password::defaults()],
        ];
    }
}
