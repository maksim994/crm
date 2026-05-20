<?php

namespace App\Http\Middleware;

use App\Enums\UserRole;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureClientUser
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user === null
            || $user->role !== UserRole::ClientUser
            || $user->agency_client_id === null
            || ! $user->is_active) {
            abort(403);
        }

        return $next($request);
    }
}
