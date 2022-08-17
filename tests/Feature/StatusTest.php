<?php

namespace Tests\Feature;

use Tests\TestCase;

class StatusTest extends TestCase
{
    /**
     * A basic test example.
     *
     * @return void
     */
    public function testApiStatus(): void
    {
        $this->get('/api/v1/status')->assertStatus(200);
    }
}
