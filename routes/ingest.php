<?php

use App\Http\Controllers\Ingest\SeoLeadController;
use Illuminate\Support\Facades\Route;

Route::middleware('throttle:ingest')->group(function () {
    Route::match(['get', 'post'], '/seolead', [SeoLeadController::class, 'store']);
});
