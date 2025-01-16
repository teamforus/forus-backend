<?php

namespace Tests\Traits;

use App\Models\Fund;
use App\Models\FundRequest;
use App\Models\FundRequestRecord;
use App\Models\Identity;
use App\Models\Organization;
use App\Traits\DoesTesting;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Illuminate\Testing\TestResponse;

trait MakesTestFundRequests
{
    use DoesTesting;

    /**
     * @param Identity $identity
     * @param Fund $fund
     * @param mixed $records
     * @param bool $validate
     * @param array $headers
     * @return TestResponse
     */
    protected function makeFundRequest(
        Identity $identity,
        Fund $fund,
        mixed $records,
        bool $validate,
        array $headers = []
    ): TestResponse {
        $url = "/api/v1/platform/funds/$fund->id/requests" . ($validate ? "/validate" : "");
        $proxy = $this->makeIdentityProxy($identity);
        $identity->setBsnRecord('123456789');

        return $this->postJson($url, compact('records'), $this->makeApiHeaders($proxy, $headers));
    }

    /**
     * @param FundRequest $fundRequest
     * @param FundRequestRecord $fundRequestRecord
     * @param string|int $value
     * @return TestResponse
     */
    protected function updateFundRequestRecordRequest(
        FundRequest $fundRequest,
        FundRequestRecord $fundRequestRecord,
        string|int $value,
    ): TestResponse {
        $organization = $fundRequest->fund->organization;

        return $this->patchJson(
            "/api/v1/platform/organizations/$organization->id/fund-requests/$fundRequest->id/records/$fundRequestRecord->id",
            ['value' => $value],
            $this->makeApiHeaders($organization->identity),
        );
    }

    /**
     * @param Organization $organization
     * @param FundRequest $fundRequest
     * @param array $headers
     * @return void
     */
    protected function assertFundRequestCreateEmailLog(
        Organization $organization,
        FundRequest $fundRequest,
        array $headers = [],
    ): void {
        // assert email log exists
        $response = $this->getJson(
            "/api/v1/platform/organizations/$organization->id/fund-requests/$fundRequest->id/email-logs",
            $this->makeApiHeaders($this->makeIdentityProxy($organization->identity), $headers),
        );

        $response->assertSuccessful();
        $data = $response->json('data');

        self::assertCount(1, $data);
        self::assertCount(1, Arr::where($data, function ($item) {
            return $item['type'] == 'fund_request_created';
        }));
    }

    /**
     * @param Organization $organization
     * @param FundRequest $fundRequest
     * @param array $headers
     * @return string
     */
    protected function requestFundRequestClarification(
        Organization $organization,
        FundRequest $fundRequest,
        array $headers = [],
    ): string {
        $questionToken = token_generator()->generate(200);
        $fundRequestRecord = $fundRequest->records[0];

        // assert email log exists
        $response = $this->postJson(
            "/api/v1/platform/organizations/$organization->id/fund-requests/$fundRequest->id/clarifications",
            [
                'fund_request_record_id' => $fundRequestRecord->id,
                'question' => $questionToken,
            ],
            $this->makeApiHeaders($this->makeIdentityProxy($organization->identity), $headers),
        );

        $response->assertSuccessful();

        return $questionToken;
    }

    /**
     * @param Organization $organization
     * @param FundRequest $fundRequest
     * @param string $questionToken
     * @param array $headers
     * @return void
     */
    protected function assertFundRequestClarificationEmailLog(
        Organization $organization,
        FundRequest $fundRequest,
        string $questionToken,
        array $headers = [],
    ): void {
        // assert email log exists
        $response = $this->getJson(
            "/api/v1/platform/organizations/$organization->id/fund-requests/$fundRequest->id/email-logs",
            $this->makeApiHeaders($this->makeIdentityProxy($organization->identity), $headers),
        );

        $response->assertSuccessful();
        $data = $response->json('data');

        self::assertCount(2, $data);
        self::assertCount(1, Arr::where($data, function ($item) use ($questionToken) {
            return
                $item['type'] == 'fund_request_feedback_requested' &&
                Str::contains($item['content'], $questionToken);
        }));
    }
}