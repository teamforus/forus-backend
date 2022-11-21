<?php

namespace Tests;

use App\Traits\DoesTesting;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Tests\Traits\MakesTestIdentities;

abstract class TestCase extends BaseTestCase
{
    use CreatesApplication, DoesTesting, MakesTestIdentities;

    /**
     * @var string[]
     */
    protected $defaultHeaders = [
        'Accept' => 'application/json',
        'client_type' => 'webshop',
    ];
}
