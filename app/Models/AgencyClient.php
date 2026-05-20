<?php

namespace App\Models;

use App\Enums\AgencyClientStatus;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;

class AgencyClient extends Model
{
    use HasUuids;

    protected $fillable = [
        'name',
        'inn',
        'contact_name',
        'contact_email',
        'contact_phone',
        'manager_comment',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'status' => AgencyClientStatus::class,
        ];
    }

    public function sites(): HasMany
    {
        return $this->hasMany(Site::class);
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function leads(): HasManyThrough
    {
        return $this->hasManyThrough(Lead::class, Site::class);
    }
}
