<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class LinkAccountRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // rotta protetta da auth:sanctum
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            // email o username dell'account sul sito SNA.
            'identifier' => ['required', 'string', 'max:255'],
            // password dell'account SNA: verificata contro WordPress prima del collegamento.
            'password'   => ['required', 'string'],
        ];
    }
}
