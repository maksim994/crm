<?php

namespace App\Support;

use App\Enums\UserRole;
use App\Models\AgencyClient;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class CabinetImpersonation
{
    private const CACHE_PREFIX = 'cabinet_impersonate:';

    private const TTL_SECONDS = 120;

    public static function issue(AgencyClient $client, ?User $cabinetUser = null): string
    {
        $user = $cabinetUser ?? static::resolveCabinetUser($client);

        $token = Str::random(48);

        Cache::put(static::CACHE_PREFIX.$token, $user->id, static::TTL_SECONDS);

        return $token;
    }

    public static function consume(string $token): ?User
    {
        $cacheKey = static::CACHE_PREFIX.$token;
        $userId = Cache::pull($cacheKey);

        if ($userId === null) {
            return null;
        }

        $user = User::query()->find($userId);

        if ($user === null
            || $user->role !== UserRole::ClientUser
            || ! $user->is_active
            || $user->agency_client_id === null) {
            return null;
        }

        return $user;
    }

    public static function resolveCabinetUser(AgencyClient $client, ?int $userId = null): User
    {
        $query = $client->users()
            ->where('role', UserRole::ClientUser)
            ->where('is_active', true);

        if ($userId !== null) {
            $user = $query->whereKey($userId)->first();

            if ($user === null) {
                abort(422, 'Пользователь ЛК не найден или неактивен.');
            }

            return $user;
        }

        $user = $query->orderBy('email')->first();

        if ($user === null) {
            abort(422, 'Нет активных пользователей личного кабинета для этого заказчика.');
        }

        return $user;
    }
}
