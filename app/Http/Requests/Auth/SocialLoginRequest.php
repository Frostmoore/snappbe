<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;

class SocialLoginRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            // token = id_token/access_token ottenuto dall'app nativamente dal provider.
            'token' => ['required', 'string'],
            'device_name' => ['sometimes', 'string', 'max:255'],
            // Nome display: lo manda l'app SOLO per Apple, e solo al primo consenso
            // (Apple non lo mette nel token). Dato client non verificato → solo
            // fallback alla creazione, mai sovrascrive un nome esistente.
            'name' => ['sometimes', 'nullable', 'string', 'max:100'],
        ];
    }
}
