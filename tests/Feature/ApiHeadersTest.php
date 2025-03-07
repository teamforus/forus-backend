<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Str;
use Tests\TestCase;
use Tests\Traits\MakesProductReservations;
use Tests\Traits\MakesTestFundProviders;
use Tests\Traits\MakesTestFunds;
use Tests\Traits\MakesTestOrganizations;
use Tests\Traits\TestsReservations;
use Throwable;

class ApiHeadersTest extends TestCase
{
    use MakesTestFunds;
    use TestsReservations;
    use DatabaseTransactions;
    use MakesTestOrganizations;
    use MakesProductReservations;
    use MakesTestFundProviders;

    /**
     * @throws Throwable
     * @return void
     */
    public function testImplementationKey(): void
    {
        $res = $this->getJson('/api/v1/platform/config/webshop', [
            'Client-Type' => Str::random(32),
            'Client-Key' => Str::random(32),
        ]);

        $res->assertForbidden();
        $res->assertExactJson(['message' => 'unknown_implementation_key']);
    }

    /**
     * @throws Throwable
     * @return void
     */
    public function testClientType(): void
    {
        $fund = $this->makeTestFund($this->makeTestOrganization($this->makeIdentity()));

        $res = $this->getJson('/api/v1/platform/config/webshop', [
            'Client-Type' => Str::random(32),
            'Client-Key' => $fund->getImplementation()->key,
        ]);

        $res->assertForbidden();
        $res->assertExactJson(['message' => 'unknown_client_type']);
    }
}
