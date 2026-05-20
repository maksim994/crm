<?php

namespace App\Policies;

use App\Models\Lead;
use App\Models\User;
use App\Support\ClientSiteAccess;

class LeadPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isClientUser()
            && $user->agency_client_id !== null
            && $user->is_active;
    }

    public function view(User $user, Lead $lead): bool
    {
        if (! $user->isClientUser() || $user->agency_client_id === null || ! $user->is_active) {
            return false;
        }

        return ClientSiteAccess::canAccessSite($user, (string) $lead->site_id);
    }
}
