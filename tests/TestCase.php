<?php

namespace Tests;

use App\Models\Implementation;
use App\Services\MailDatabaseLoggerService\Traits\AssertsSentEmails;
use App\Traits\DoesTesting;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Tests\Traits\MakesTestIdentities;

abstract class TestCase extends BaseTestCase
{
    use CreatesApplication, DoesTesting, MakesTestIdentities, AssertsSentEmails;

    /**
     * @var string[]
     */
    protected $defaultHeaders = [
        'Accept' => 'application/json',
        'Client-Type' => 'webshop',
    ];

    /**
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();
        Implementation::clearMemo();
    }

    /**
     * @param $key
     * @return Implementation
     */
    protected function findImplementation($key = null): Implementation
    {
        return $key ? Implementation::byKey($key) : Implementation::general();
    }
}
