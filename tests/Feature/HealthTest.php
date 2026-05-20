<?php

namespace Tests\Feature;

use Tests\TestCase;

class HealthTest extends TestCase
{
    public function test_health_endpoint_returns_ok(): void
    {
        $response = $this->get('/health');

        $response->assertOk();
        $response->assertJsonStructure([
            'status',
            'checks' => ['app', 'database', 'redis', 'session'],
        ]);
    }

    public function test_ready_health_endpoint_returns_ok(): void
    {
        $response = $this->get('/health/ready');

        $response->assertOk();
        $response->assertJsonStructure([
            'status',
            'checks' => ['app', 'database', 'redis', 'session', 'session_start', 'crypto', 'session_driver'],
        ]);
    }

    public function test_web_stack_health_endpoint_returns_ok(): void
    {
        $response = $this->get('/health/web-stack');

        $response->assertOk();
        $response->assertJson(['status' => 'ok', 'middleware' => 'web']);
    }
}
