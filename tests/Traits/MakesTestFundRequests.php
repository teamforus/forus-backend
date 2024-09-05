<?php

namespace Tests\Traits;

use App\Models\Fund;
use App\Models\FundRequest;
use App\Models\Identity;
use App\Models\Organization;
use App\Traits\DoesTesting;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;

trait MakesTestFundRequests
{
    use DoesTesting;

    /**
     * @param Fund $fund
     * @param Identity $requesterIdentity
     * @param array $headers
     * @return FundRequest
     */
    protected function makeFundRequest(Fund $fund, Identity $requesterIdentity, array $headers = []): FundRequest
    {
        $requesterIdentityAuth = $this->makeApiHeaders($this->makeIdentityProxy($requesterIdentity), $headers);

        // make the fund request
        $response = $this->postJson("/api/v1/platform/funds/$fund->id/requests", [
            'records' => [[
                'fund_criterion_id' => $fund->criteria[0]?->id,
                'value' => 5,
                'files' => [],
            ]]
        ], $requesterIdentityAuth);

        $response->assertSuccessful();
        $fundRequest = FundRequest::find($response->json('data.id'));

        self::assertNotNull($fundRequest);

        return $fundRequest;
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