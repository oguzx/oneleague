<?php

namespace Tests\Feature;

use Tests\TestCase;

class ExampleTest extends TestCase
{
    /**
     * A basic test example.
     */
    public function test_the_application_returns_a_successful_response(): void
    {
        // Force an in-memory session driver so the welcome route does not
        // require a `sessions` database table during feature tests.
        config(['session.driver' => 'array']);

        $response = $this->get('/');

        $response->assertStatus(200);
    }
}
