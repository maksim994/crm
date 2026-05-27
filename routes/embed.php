<?php

use App\Http\Controllers\Embed\EmbedConfigController;
use Illuminate\Support\Facades\Route;

Route::get('/config', [EmbedConfigController::class, 'show']);
