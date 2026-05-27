<?php

use App\Http\Controllers\Api\Admin\AuthController;
use App\Http\Controllers\Api\Admin\AgencyClientController;
use App\Http\Controllers\Api\Admin\ClientCabinetImpersonateController;
use App\Http\Controllers\Api\Admin\ClientCabinetUserController;
use App\Http\Controllers\Api\Admin\DashboardController;
use App\Http\Controllers\Api\Admin\DiagnosticsController;
use App\Http\Controllers\Api\Admin\DocumentationController;
use App\Http\Controllers\Api\Admin\LeadController;
use App\Http\Controllers\Api\Admin\PlatformAdminUserController;
use App\Http\Controllers\Api\Admin\SiteController;
use App\Http\Controllers\Api\Client\AnalyticsController;
use App\Http\Controllers\Api\Client\AuthController as ClientAuthController;
use App\Http\Controllers\Api\Client\ImpersonateController;
use App\Http\Controllers\Api\Client\LeadController as ClientLeadController;
use App\Http\Controllers\Api\Client\SiteController as ClientSiteController;
use Illuminate\Support\Facades\Route;

Route::prefix('client')->group(function () {
    Route::post('impersonate', [ImpersonateController::class, 'store']);
    Route::post('login', [ClientAuthController::class, 'login']);

    Route::middleware(['auth:sanctum', 'client.user'])->group(function () {
        Route::post('logout', [ClientAuthController::class, 'logout']);
        Route::get('user', [ClientAuthController::class, 'user']);
        Route::get('sites', [ClientSiteController::class, 'index']);
        Route::get('leads/export', [ClientLeadController::class, 'export']);
        Route::get('leads', [ClientLeadController::class, 'index']);
        Route::get('leads/{lead}', [ClientLeadController::class, 'show']);

        Route::prefix('projects/{site}/analytics')->group(function () {
            Route::get('traffic-sources', [AnalyticsController::class, 'trafficSources']);
            Route::get('search-engines', [AnalyticsController::class, 'searchEngines']);
            Route::get('search-branded', [AnalyticsController::class, 'searchBranded']);
            Route::get('search-non-branded', [AnalyticsController::class, 'searchNonBranded']);
            Route::get('geography', [AnalyticsController::class, 'geography']);
            Route::get('devices', [AnalyticsController::class, 'devices']);
        });
    });
});

Route::prefix('admin')->group(function () {
    Route::post('login', [AuthController::class, 'login']);

    Route::middleware(['auth:sanctum', 'platform.admin'])->group(function () {
        Route::post('logout', [AuthController::class, 'logout']);
        Route::get('user', [AuthController::class, 'user']);
        Route::get('dashboard', DashboardController::class);
        Route::get('diagnostics', [DiagnosticsController::class, 'index']);
        Route::get('sites/{site}/diagnostics', [DiagnosticsController::class, 'site']);

        Route::get('docs', [DocumentationController::class, 'index']);
        Route::get('docs/{slug}', [DocumentationController::class, 'show']);
        Route::apiResource('platform-admins', PlatformAdminUserController::class)
            ->parameters(['platform-admins' => 'platformAdmin']);
        Route::apiResource('clients', AgencyClientController::class);
        Route::apiResource('clients.cabinet-users', ClientCabinetUserController::class)
            ->parameters(['cabinet-users' => 'cabinetUser']);
        Route::post('clients/{client}/impersonate', [ClientCabinetImpersonateController::class, 'store'])
            ->name('clients.impersonate');
        Route::apiResource('sites', SiteController::class);
        Route::post('sites/{site}/regenerate-token', [SiteController::class, 'regenerateToken'])->name('sites.regenerate-token');

        Route::get('leads', [LeadController::class, 'index']);
        Route::post('leads', [LeadController::class, 'store']);
        Route::get('leads/{lead}', [LeadController::class, 'show']);
        Route::put('leads/{lead}', [LeadController::class, 'update']);
    });
});
