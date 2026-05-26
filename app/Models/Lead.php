<?php

namespace App\Models;

use App\Enums\LeadChannel;
use App\Enums\LeadStatus;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Lead extends Model
{
    use HasUuids;

    public $timestamps = false;

    protected $fillable = [
        'site_id',
        'channel',
        'phone',
        'email',
        'contact_name',
        'form_description',
        'lead_status',
        'manager_name',
        'inn',
        'city',
        'product_request',
        'comment',
        'sku_count',
        'manager_comment',
        'expected_amount',
        'metrika_client_id',
        'utm_source',
        'utm_medium',
        'utm_campaign',
        'utm_term',
        'utm_content',
        'utm_campaign_first',
        'advertising_channel',
        'landing_domain',
        'visitor_ip',
        'call_recording_url',
        'call_duration_sec',
        'is_duplicate',
        'acc_status',
        'acc_comment',
        'ppc_status',
        'ppc_comment',
        'acc_ppc_summary',
        'raw_payload',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'channel' => LeadChannel::class,
            'lead_status' => LeadStatus::class,
            'is_duplicate' => 'boolean',
            'raw_payload' => 'array',
            'expected_amount' => 'decimal:2',
            'created_at' => 'datetime',
        ];
    }

    public function site(): BelongsTo
    {
        return $this->belongsTo(Site::class);
    }
}
