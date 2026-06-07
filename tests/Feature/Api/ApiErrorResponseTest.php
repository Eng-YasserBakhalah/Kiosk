<?php

namespace Tests\Feature\Api;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ApiErrorResponseTest extends TestCase
{
    use RefreshDatabase;

    public function test_protected_api_routes_return_json_without_accept_header(): void
    {
        $response = $this->get('/api/v1/services');

        $response
            ->assertUnauthorized()
            ->assertHeader('content-type', 'application/json')
            ->assertJsonPath('success', false)
            ->assertJsonPath('error.code', 'SESSION_EXPIRED');
    }
}
