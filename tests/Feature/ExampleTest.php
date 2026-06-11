<?php

namespace Tests\Feature;

use Tests\TestCase;

class ExampleTest extends TestCase
{
    public function test_root_returns_welcome_page(): void
    {
        $response = $this->get('/');

        $response->assertOk();
        $response->assertSee('Lead CRM', false);
        $response->assertSee('Войти в админку', false);
        $response->assertSee('Личный кабинет', false);
        $response->assertSee('/admin', false);
        $response->assertSee('/cabinet', false);
    }
}
