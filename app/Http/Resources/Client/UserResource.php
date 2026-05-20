<?php

namespace App\Http\Resources\Client;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\User */
class UserResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'role' => $this->role->value,
            'agency_client' => $this->whenLoaded('agencyClient', fn () => [
                'id' => $this->agencyClient->id,
                'name' => $this->agencyClient->name,
            ]),
        ];
    }
}
