<?php

namespace App\Services;

use App\Models\Lead;
use App\Models\Site;
use App\Support\PhoneNormalizer;
use Illuminate\Support\Carbon;

class DuplicateLeadDetector
{
    public function isDuplicate(Site $site, ?string $phone, ?string $email, ?Carbon $before = null): bool
    {
        $since = ($before ?? now())->copy()->subDays(30);
        $normalizedPhone = PhoneNormalizer::normalize($phone);
        $normalizedEmail = $email !== null && trim($email) !== ''
            ? strtolower(trim($email))
            : null;

        if ($normalizedPhone === null && $normalizedEmail === null) {
            return false;
        }

        $query = Lead::query()
            ->where('site_id', $site->id)
            ->where('created_at', '>=', $since);

        if ($before !== null) {
            $query->where('created_at', '<=', $before);
        }

        $candidates = $query->get(['phone', 'email']);

        foreach ($candidates as $lead) {
            if ($normalizedPhone !== null
                && PhoneNormalizer::normalize($lead->phone) === $normalizedPhone) {
                return true;
            }

            if ($normalizedEmail !== null
                && strtolower(trim((string) $lead->email)) === $normalizedEmail) {
                return true;
            }
        }

        return false;
    }
}
