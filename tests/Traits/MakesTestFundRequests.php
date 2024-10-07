<?php

namespace Tests\Traits;

use App\Models\Fund;
use App\Models\Identity;
use Illuminate\Testing\TestResponse;

trait MakesTestFundRequests
{
    /**
     * @param Identity $identity
     * @param Fund $fund
     * @param mixed $records
     * @param bool $validate
     * @return TestResponse
     */
    protected function makeFundRequest(
        Identity $identity,
        Fund $fund,
        mixed $records,
        bool $validate,
    ): TestResponse {
        $url = "/api/v1/platform/funds/$fund->id/requests" . ($validate ? "/validate" : "");
        $proxy = $this->makeIdentityProxy($identity);
        $identity->setBsnRecord('123456789');

        return $this->postJson($url, compact('records'), $this->makeApiHeaders($proxy));
    }
}