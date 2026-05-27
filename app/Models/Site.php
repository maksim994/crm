<?php

namespace App\Models;

use App\Enums\SiteStatus;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Str;
use Illuminate\Contracts\Encryption\DecryptException;

class Site extends Model
{
    use HasUuids;

    protected $fillable = [
        'agency_client_id',
        'name',
        'domains',
        'metrika_counter_id',
        'metrika_brand_keywords',
        'timezone',
        'token_hash',
        'status',
        'email_inbound_address',
        'email_inbound_seo',
        'email_inbound_other',
    ];

    /**
     * @return list<string>
     */
    public function inboundEmailAddresses(): array
    {
        return array_values(array_filter(array_map(
            static fn (?string $email) => $email !== null ? strtolower(trim($email)) : null,
            [
                $this->email_inbound_address,
                $this->email_inbound_seo,
                $this->email_inbound_other,
            ],
        )));
    }

    public static function findByInboundEmail(string $address): ?self
    {
        $address = strtolower(trim($address));

        if ($address === '') {
            return null;
        }

        return static::query()
            ->where(function ($query) use ($address) {
                $query->whereRaw('LOWER(email_inbound_address) = ?', [$address])
                    ->orWhereRaw('LOWER(email_inbound_seo) = ?', [$address])
                    ->orWhereRaw('LOWER(email_inbound_other) = ?', [$address]);
            })
            ->first();
    }

    protected function casts(): array
    {
        return [
            'domains' => 'array',
            'metrika_brand_keywords' => 'array',
            'status' => SiteStatus::class,
        ];
    }

    public function agencyClient(): BelongsTo
    {
        return $this->belongsTo(AgencyClient::class);
    }

    public function leads(): HasMany
    {
        return $this->hasMany(Lead::class);
    }

    public function cabinetUsers(): BelongsToMany
    {
        return $this->belongsToMany(User::class);
    }

    /**
     * Выдаёт токен вида {uuid}:{secret}, сохраняет sha256-хэш и зашифрованную копию для админки.
     *
     * @return string Plaintext token
     */
    public function issueToken(): string
    {
        $plain = Str::random(32);
        $token = $this->id.':'.$plain;

        $this->forceFill([
            'token_hash' => self::hashToken($token),
            'token_encrypted' => Crypt::encryptString($token),
        ])->save();

        return $token;
    }

    public function plainToken(): ?string
    {
        if (blank($this->token_encrypted)) {
            return null;
        }

        try {
            return Crypt::decryptString($this->token_encrypted);
        } catch (DecryptException) {
            return null;
        }
    }

    public function hasIssuedToken(): bool
    {
        return filled($this->token_hash);
    }

    public static function findByToken(string $token): ?self
    {
        if (! str_contains($token, ':')) {
            return null;
        }

        [$id] = explode(':', $token, 2);

        $site = static::query()->find($id);

        if ($site === null) {
            return null;
        }

        return hash_equals($site->token_hash, self::hashToken($token)) ? $site : null;
    }

    public static function hashToken(string $token): string
    {
        return hash('sha256', $token);
    }

    public function acceptsLeads(): bool
    {
        return $this->status->acceptsLeads();
    }
}
