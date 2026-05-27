<?php

namespace App\Http\Resources\Admin;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\Site */
class SiteResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'agency_client_id' => $this->agency_client_id,
            'agency_client' => $this->whenLoaded('agencyClient', fn () => [
                'id' => $this->agencyClient->id,
                'name' => $this->agencyClient->name,
            ]),
            'name' => $this->name,
            'domains' => $this->domains ?? [],
            'metrika_counter_id' => $this->metrika_counter_id,
            'metrika_brand_keywords' => $this->metrika_brand_keywords ?? [],
            'timezone' => $this->timezone,
            'status' => $this->status->value,
            'email_inbound_address' => $this->email_inbound_address,
            'email_inbound_seo' => $this->email_inbound_seo,
            'email_inbound_other' => $this->email_inbound_other,
            'leads_count' => $this->whenCounted('leads'),
            'integration' => $this->when(isset($this->integration), $this->integration),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
