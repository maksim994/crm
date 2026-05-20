<?php

namespace App\Support;

use App\Models\Site;
use App\Models\User;
use Illuminate\Support\Collection;

class ClientSiteAccess
{
    public static function restrictsSites(User $user): bool
    {
        return ! $user->cabinet_all_sites;
    }

    /**
     * @return list<string>
     */
    public static function allowedSiteIds(User $user): array
    {
        if (! $user->isClientUser() || $user->agency_client_id === null) {
            return [];
        }

        if ($user->cabinet_all_sites) {
            return Site::query()
                ->where('agency_client_id', $user->agency_client_id)
                ->pluck('id')
                ->all();
        }

        return $user->sites()
            ->where('agency_client_id', $user->agency_client_id)
            ->pluck('sites.id')
            ->all();
    }

    public static function canAccessSite(User $user, string $siteId): bool
    {
        return in_array($siteId, static::allowedSiteIds($user), true);
    }

    /**
     * @return Collection<int, Site>
     */
    public static function allowedSitesQuery(User $user)
    {
        return Site::query()
            ->whereIn('id', static::allowedSiteIds($user))
            ->orderBy('name');
    }
}
