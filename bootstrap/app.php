<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
        then: function () {
            \Illuminate\Support\Facades\Route::middleware('api')
                ->get('/health', \App\Http\Controllers\HealthController::class);

            \Illuminate\Support\Facades\Route::middleware('web')
                ->get('/health/web-stack', \App\Http\Controllers\WebStackHealthController::class);

            \Illuminate\Support\Facades\Route::prefix('sanctum')
                ->middleware('spa')
                ->get('/csrf-cookie', [\Laravel\Sanctum\Http\Controllers\CsrfCookieController::class, 'show'])
                ->name('sanctum.csrf-cookie');

            \Illuminate\Support\Facades\Route::middleware('api')
                ->prefix('ingest')
                ->group(base_path('routes/ingest.php'));

            \Illuminate\Support\Facades\Route::middleware('api')
                ->prefix('api/v1')
                ->group(base_path('routes/api_v1.php'));
        },
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->trustProxies(at: '*');

        $middleware->web(remove: [
            \Illuminate\View\Middleware\ShareErrorsFromSession::class,
        ]);

        $middleware->group('spa', [
            \Illuminate\Cookie\Middleware\EncryptCookies::class,
            \Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse::class,
            \Illuminate\Session\Middleware\StartSession::class,
        ]);

        $middleware->statefulApi();

        $middleware->alias([
            'platform.admin' => \App\Http\Middleware\EnsurePlatformAdmin::class,
            'client.user' => \App\Http\Middleware\EnsureClientUser::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        $exceptions->reportable(function (\Throwable $exception): void {
            if (app()->environment('production')) {
                error_log(sprintf(
                    'wbooster.exception: %s in %s:%d',
                    $exception->getMessage(),
                    $exception->getFile(),
                    $exception->getLine(),
                ));
            }
        });
    })->create();
