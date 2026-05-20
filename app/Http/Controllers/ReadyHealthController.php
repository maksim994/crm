<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;

class ReadyHealthController extends Controller
{
    public function __invoke(HealthController $health): JsonResponse
    {
        return $health->readyResponse();
    }
}
