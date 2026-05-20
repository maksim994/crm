<?php

use App\Http\Controllers\Ingest\CallWebhookController;
use Illuminate\Support\Facades\Route;

Route::middleware('throttle:ingest')->group(function () {
    Route::post('leads/call', [CallWebhookController::class, 'store']);
});
