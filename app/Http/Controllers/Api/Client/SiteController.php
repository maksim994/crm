<?php

namespace App\Http\Controllers\Api\Client;

use App\Http\Controllers\Controller;
use App\Http\Resources\Client\SiteResource;
use App\Support\ClientSiteAccess;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class SiteController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $sites = ClientSiteAccess::allowedSitesQuery($request->user())
            ->get(['id', 'name']);

        return SiteResource::collection($sites);
    }
}
