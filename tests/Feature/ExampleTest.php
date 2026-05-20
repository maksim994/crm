<?php

namespace Tests\Feature;

use Tests\TestCase;

class ExampleTest extends TestCase
{
    public function test_root_returns_app_name(): void
    {
        $response = $this->get('/');

        $response->assertOk();
        $response->assertJsonFragment(['name' => 'WBooster CRM']);
    }
}
