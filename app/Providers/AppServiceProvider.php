<?php

namespace App\Providers;

use App\Models\Lead;
use App\Policies\LeadPolicy;
use App\Support\IngestToken;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        Gate::policy(Lead::class, LeadPolicy::class);

        RateLimiter::for('ingest', function (Request $request) {
            $token = IngestToken::fromRequest($request);

            return Limit::perMinute(60)->by($token.'|'.$request->ip());
        });
    }
}
