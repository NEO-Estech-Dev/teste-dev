<?php

namespace Tests\Feature;

use Tests\TestCase;

class ApplicationEndpointsTest extends TestCase
{
    public function test_root_lists_api_endpoints(): void
    {
        $this->getJson('/')
            ->assertOk()
            ->assertJsonPath('name', config('app.name'))
            ->assertJsonStructure([
                'name',
                'message',
                'documentation',
                'endpoints',
            ]);
    }

    public function test_health_endpoint_is_available(): void
    {
        $this->getJson('/up')->assertOk();
    }

    public function test_api_documentation_is_available(): void
    {
        $this->get('/docs/api')->assertOk();
    }
}
