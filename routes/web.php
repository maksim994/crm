<?php

use App\Http\Controllers\HealthController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return response()->json([
        'name' => 'WBooster CRM',
        'docs' => '/docs/PROEKT.md',
        'admin' => '/admin',
        'cabinet' => '/cabinet',
    ]);
});

Route::get('/health', HealthController::class);
