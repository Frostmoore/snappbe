<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin \App\Models\AccountLink
 */
class AccountLinkResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'status' => $this->status->value,
            'linked_at' => $this->linked_at,
            'wp_account' => $this->whenLoaded('wpAccount', fn () => [
                'wp_user_id' => $this->wpAccount->wp_user_id,
                'username' => $this->wpAccount->username,
                'email' => $this->wpAccount->email,
                'level' => $this->wpAccount->level,
                'level_label' => $this->wpAccount->level_label,
            ]),
        ];
    }
}
