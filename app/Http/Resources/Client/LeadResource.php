<?php

namespace App\Http\Resources\Client;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\Lead */
class LeadResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'site_id' => $this->site_id,
            'site' => $this->whenLoaded('site', fn () => [
                'id' => $this->site->id,
                'name' => $this->site->name,
            ]),
            'channel' => $this->channel->value,
            'channel_label' => $this->channel->label(),
            'phone' => $this->phone,
            'email' => $this->email,
            'contact_name' => $this->contact_name,
            'form_description' => $this->form_description,
            'lead_status' => $this->lead_status->value,
            'lead_status_label' => $this->lead_status->label(),
            'city' => $this->city,
            'product_request' => $this->product_request,
            'sku_count' => $this->sku_count,
            'metrika_client_id' => $this->metrika_client_id,
            'utm_source' => $this->utm_source,
            'utm_medium' => $this->utm_medium,
            'utm_campaign' => $this->utm_campaign,
            'utm_term' => $this->utm_term,
            'utm_content' => $this->utm_content,
            'utm_campaign_first' => $this->utm_campaign_first,
            'advertising_channel' => $this->advertising_channel,
            'landing_domain' => $this->landing_domain,
            'call_recording_url' => $this->call_recording_url,
            'call_duration_sec' => $this->call_duration_sec,
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
