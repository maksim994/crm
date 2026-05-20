<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return response()->json([
        'name' => 'WBooster CRM',
        'docs' => '/docs/PROEKT.md',
        'admin' => '/admin',
        'cabinet' => '/cabinet',
    ]);
});
