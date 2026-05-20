<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;

class HealthController extends Controller
{
    public function __invoke(): JsonResponse
    {
        $checks = [
            'app' => 'ok',
            'database' => $this->checkDatabase(),
            'redis' => $this->checkRedis(),
            'session' => $this->checkSessionStorage(),
        ];

        $healthy = ! in_array('error', $checks, true);

        return response()->json([
            'status' => $healthy ? 'ok' : 'degraded',
            'checks' => $checks,
        ], $healthy ? 200 : 503);
    }

    private function checkDatabase(): string
    {
        try {
            DB::connection()->getPdo();

            return 'ok';
        } catch (\Throwable) {
            return 'error';
        }
    }

    private function checkRedis(): string
    {
        try {
            Redis::ping();

            return 'ok';
        } catch (\Throwable) {
            return 'error';
        }
    }

    private function checkSessionStorage(): string
    {
        try {
            $driver = config('session.driver');

            if ($driver === 'file') {
                $path = (string) config('session.files');

                return is_dir($path) && is_writable($path) ? 'ok' : 'error';
            }

            if ($driver === 'redis') {
                $key = 'health_session_probe';
                Redis::setex($key, 10, '1');
                Redis::del($key);

                return 'ok';
            }

            return 'ok';
        } catch (\Throwable) {
            return 'error';
        }
    }
}
