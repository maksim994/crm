<?php

namespace App\Http\Resources\Admin;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\User */
class CabinetUserResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'cabinet_all_sites' => (bool) $this->cabinet_all_sites,
            'is_active' => (bool) $this->is_active,
            'site_ids' => $this->whenLoaded('sites', fn () => $this->sites->pluck('id')->values()->all()),
            'sites' => $this->whenLoaded('sites', fn () => $this->sites->map(fn ($site) => [
                'id' => $site->id,
                'name' => $site->name,
            ])->values()->all()),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
