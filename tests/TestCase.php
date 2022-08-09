<?php

namespace Tests;

use App\Traits\DoesTesting;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    use CreatesApplication, DoesTesting;

    /**
     * @var string[]
     */
    protected $defaultHeaders = [
        'Accept' => 'application/json',
        'client_type' => 'webshop',
    ];
}
