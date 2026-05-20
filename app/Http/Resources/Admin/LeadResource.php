<?php

namespace App\Http\Resources\Admin;

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
                'agency_client' => $this->site->relationLoaded('agencyClient') ? [
                    'id' => $this->site->agencyClient->id,
                    'name' => $this->site->agencyClient->name,
                ] : null,
            ]),
            'channel' => $this->channel->value,
            'channel_label' => $this->channel->label(),
            'phone' => $this->phone,
            'email' => $this->email,
            'contact_name' => $this->contact_name,
            'form_description' => $this->form_description,
            'lead_status' => $this->lead_status->value,
            'lead_status_label' => $this->lead_status->label(),
            'manager_name' => $this->manager_name,
            'manager_comment' => $this->manager_comment,
            'inn' => $this->inn,
            'city' => $this->city,
            'product_request' => $this->product_request,
            'sku_count' => $this->sku_count,
            'expected_amount' => $this->expected_amount,
            'metrika_client_id' => $this->metrika_client_id,
            'utm_source' => $this->utm_source,
            'utm_medium' => $this->utm_medium,
            'utm_campaign' => $this->utm_campaign,
            'utm_term' => $this->utm_term,
            'utm_content' => $this->utm_content,
            'utm_campaign_first' => $this->utm_campaign_first,
            'advertising_channel' => $this->advertising_channel,
            'landing_domain' => $this->landing_domain,
            'visitor_ip' => $this->visitor_ip,
            'call_recording_url' => $this->call_recording_url,
            'call_duration_sec' => $this->call_duration_sec,
            'is_duplicate' => $this->is_duplicate,
            'acc_status' => $this->acc_status,
            'acc_comment' => $this->acc_comment,
            'ppc_status' => $this->ppc_status,
            'ppc_comment' => $this->ppc_comment,
            'acc_ppc_summary' => $this->acc_ppc_summary,
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
