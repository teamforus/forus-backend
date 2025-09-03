<?php

namespace Tests;

use App\Models\Implementation;
use App\Services\MailDatabaseLoggerService\Traits\AssertsSentEmails;
use App\Traits\DoesTesting;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Tests\Traits\MakesApiRequests;
use Tests\Traits\MakesTestIdentities;

abstract class TestCase extends BaseTestCase
{
    use DoesTesting;
    use MakesApiRequests;
    use AssertsSentEmails;
    use CreatesApplication;
    use MakesTestIdentities;

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
