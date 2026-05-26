<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MetrikaReportCache extends Model
{
    use HasUuids;

    public $timestamps = false;

    protected $table = 'metrika_report_cache';

    protected $fillable = [
        'site_id',
        'report_type',
        'date_from',
        'date_to',
        'group_by',
        'payload',
        'fetched_at',
        'expires_at',
    ];

    protected function casts(): array
    {
        return [
            'date_from' => 'date',
            'date_to' => 'date',
            'payload' => 'array',
            'fetched_at' => 'datetime',
            'expires_at' => 'datetime',
        ];
    }

    public function site(): BelongsTo
    {
        return $this->belongsTo(Site::class);
    }
}
