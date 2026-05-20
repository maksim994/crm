<?php

namespace App\Support;

use App\Models\Lead;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

class ClientLeadQuery
{
    public static function forUser(User $user, Request $request): Builder
    {
        $allowedSiteIds = ClientSiteAccess::allowedSiteIds($user);

        $query = Lead::query()
            ->with(['site'])
            ->whereIn('site_id', $allowedSiteIds ?: ['00000000-0000-0000-0000-000000000000']);

        return static::applyFilters($query, $request);
    }

    public static function applyFilters(Builder $query, Request $request): Builder
    {
        return $query
            ->when($request->filled('site_id'), fn (Builder $q) => $q->where('site_id', $request->string('site_id')))
            ->when($request->filled('channel'), fn (Builder $q) => $q->where('channel', $request->string('channel')))
            ->when($request->filled('lead_status'), fn (Builder $q) => $q->where('lead_status', $request->string('lead_status')))
            ->when($request->filled('date_from'), function (Builder $q) use ($request) {
                $q->where('created_at', '>=', $request->date('date_from')->startOfDay());
            })
            ->when($request->filled('date_to'), function (Builder $q) use ($request) {
                $q->where('created_at', '<=', $request->date('date_to')->endOfDay());
            })
            ->when($request->filled('utm_campaign'), function (Builder $q) use ($request) {
                $term = '%'.$request->string('utm_campaign').'%';
                $q->where('utm_campaign', 'like', $term);
            })
            ->orderByDesc('created_at');
    }
}
