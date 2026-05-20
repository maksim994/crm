<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;

class WebStackHealthController extends Controller
{
    public function __invoke(): JsonResponse
    {
        return response()->json([
            'status' => 'ok',
            'middleware' => 'web',
        ]);
    }
}
