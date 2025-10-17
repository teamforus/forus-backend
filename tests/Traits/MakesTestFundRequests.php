<?php

namespace Tests\Traits;

use App\Models\Fund;
use App\Models\FundRequest;
use App\Models\FundRequestRecord;
use App\Models\Identity;
use App\Models\Organization;
use App\Traits\DoesTesting;
use Illuminate\Support\Arr;
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
     * @param array $data
     * @return TestResponse
     */
    protected function makeFundRequest(
        Identity $identity,
        Fund $fund,
        mixed $records,
        bool $validate,
        array $headers = [],
        array $data = [],
    ): TestResponse {
        $url = "/api/v1/platform/funds/$fund->id/requests" . ($validate ? '/validate' : '');
        $proxy = $this->makeIdentityProxy($identity);
        $identity->setBsnRecord('123456789');

        return $this->postJson($url, [...compact('records'), ...$data], $this->makeApiHeaders($proxy, $headers));
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
            "/api/v1/platform/organizations/$organization->id/email-logs?fund_request_id=$fundRequest->id",
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
                'files_requirement' => 'required',
                'text_requirement' => 'required',
                'question' => $questionToken,
            ],
            $this->makeApiHeaders($this->makeIdentityProxy($organization->identity), [
                'Accept' => 'application/json',
                'client_type' => 'webshop',
                ...$headers,
            ]),
        );

        $response->assertSuccessful();

        return $questionToken;
    }

    /**
     * @param Organization $organization
     * @param FundRequest $fundRequest
     * @param array $headers
     * @return void
     */
    protected function assertFundRequestClarificationEmailLog(
        Organization $organization,
        FundRequest $fundRequest,
        array $headers = [],
    ): void {
        // assert email log exists
        $response = $this->getJson(
            "/api/v1/platform/organizations/$organization->id/email-logs?fund_request_id=$fundRequest->id",
            $this->makeApiHeaders($this->makeIdentityProxy($organization->identity), [
                'Accept' => 'application/json',
                'client_type' => 'webshop',
                ...$headers,
            ]),
        );

        $response->assertSuccessful();
        $data = $response->json('data');

        self::assertCount(2, $data);
        self::assertCount(1, Arr::where($data, function ($item) {
            return $item['type'] == 'fund_request_feedback_requested';
        }));
    }

    /**
     * @param Identity $requester
     * @param Fund $fund
     * @param array $records
     * @return FundRequest
     */
    protected function setCriteriaAndMakeFundRequest(Identity $requester, Fund $fund, array $records): FundRequest
    {
        $recordsList = collect($records)->map(function (string|int $value, string $key) use ($fund) {
            return $this->makeRequestCriterionValue($fund, $key, $value);
        });

        $response = $this->makeFundRequest($requester, $fund, $recordsList, false);
        $response->assertSuccessful();

        $fundRequest = FundRequest::find($response->json('data.id'));
        $this->assertNotNull($fundRequest);

        return $fundRequest;
    }
}
