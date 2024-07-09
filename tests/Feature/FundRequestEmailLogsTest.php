<?php

namespace Tests\Feature;

use App\Models\Fund;
use App\Models\FundRequest;
use App\Models\Identity;
use App\Models\Organization;
use App\Services\MediaService\Traits\UsesMediaService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Mews\Purifier\Facades\Purifier;
use Tests\TestCase;
use Tests\Traits\MakesTestFunds;
use Tests\Traits\MakesTestIdentities;
use Tests\Traits\MakesTestOrganizations;

class FundRequestEmailLogsTest extends TestCase
{
    use UsesMediaService;
    use DatabaseTransactions;
    use WithFaker;
    use MakesTestFunds;
    use MakesTestOrganizations;
    use MakesTestIdentities;

    /**
     * @throws \Throwable
     */
    public function testRequestFundEmailLogCreated()
    {
        // create sponsor and requester identities
        $sponsorIdentity = $this->makeIdentity($this->makeUniqueEmail());
        $requesterIdentity = $this->makeIdentity($this->makeUniqueEmail());
        $requesterIdentity->setBsnRecord('123456789');

        echo json_pretty(Config::get('forus.mail_purifier_config'));

        exit(Config::get('forus.mail_purifier_config'));

        // create the organization and fund
        $organization = $this->makeTestOrganization($sponsorIdentity);
        $fund = $this->makeTestFund($organization);

        // create fund request and assert email log created
        $fundRequest = $this->makeFundRequest($fund, $requesterIdentity);
        $this->assertFundRequestCreateEmailLog($organization, $fundRequest);

        $fundRequest->assignEmployee($organization->findEmployee($sponsorIdentity));

        DB::beginTransaction();
        $questionToken = $this->requestFundRequestClarification($organization, $fundRequest);
        $this->assertFundRequestClarificationEmailLog($organization, $fundRequest, $questionToken);
        DB::rollBack();

        DB::beginTransaction();
        $questionToken = $this->declineFundRequestRecord($organization, $fundRequest);
        $this->assertFundRequestRecordDeclinedEmailLog($organization, $fundRequest, $questionToken);
        DB::rollBack();

        DB::beginTransaction();
        $this->approveFundRequest($organization, $fundRequest);
        $this->assertFundRequestApprovedEmailLog($organization, $fundRequest);
        DB::rollBack();

        DB::beginTransaction();
        $this->disregardFundRequest($organization, $fundRequest, true);
        $this->assertFundRequestDisregardedEmailLog($organization, $fundRequest, true);
        DB::rollBack();

        DB::beginTransaction();
        $this->disregardFundRequest($organization, $fundRequest, false);
        $this->assertFundRequestDisregardedEmailLog($organization, $fundRequest, false);
        DB::rollBack();
    }

    /**
     * @param Fund $fund
     * @param Identity $requesterIdentity
     * @return FundRequest
     */
    protected function makeFundRequest(Fund $fund, Identity $requesterIdentity): FundRequest
    {
        $requesterIdentityAuth = $this->makeApiHeaders($this->makeIdentityProxy($requesterIdentity));

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
     * @return void
     */
    protected function assertFundRequestCreateEmailLog(
        Organization $organization,
        FundRequest $fundRequest,
    ): void {
        // assert email log exists
        $response = $this->getJson(
            "/api/v1/platform/organizations/$organization->id/fund-requests/$fundRequest->id/email-logs",
            $this->makeApiHeaders($this->makeIdentityProxy($organization->identity)),
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
     * @return string
     */
    protected function requestFundRequestClarification(
        Organization $organization,
        FundRequest $fundRequest,
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
            $this->makeApiHeaders($this->makeIdentityProxy($organization->identity)),
        );

        $response->assertSuccessful();

        return $questionToken;
    }

    /**
     * @param Organization $organization
     * @param FundRequest $fundRequest
     * @param string $questionToken
     * @return void
     */
    protected function assertFundRequestClarificationEmailLog(
        Organization $organization,
        FundRequest $fundRequest,
        string $questionToken,
    ): void {
        // assert email log exists
        $response = $this->getJson(
            "/api/v1/platform/organizations/$organization->id/fund-requests/$fundRequest->id/email-logs",
            $this->makeApiHeaders($this->makeIdentityProxy($organization->identity)),
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

    /**
     * @param Organization $organization
     * @param FundRequest $fundRequest
     * @return string
     */
    protected function declineFundRequestRecord(
        Organization $organization,
        FundRequest $fundRequest,
    ): string {
        $noteToken = token_generator()->generate(200);
        $fundRequestRecord = $fundRequest->records[0];

        $response = $this->patch(
            join([
                "/api/v1/platform/organizations/$organization->id/fund-requests/$fundRequest->id",
                "/records/$fundRequestRecord->id/decline",
            ]),
            [ 'note' => $noteToken ],
            $this->makeApiHeaders($this->makeIdentityProxy($organization->identity)),
        );

        $response->assertSuccessful();

        return $noteToken;
    }

    /**
     * @param Organization $organization
     * @param FundRequest $fundRequest
     * @param string $noteToken
     * @return void
     */
    protected function assertFundRequestRecordDeclinedEmailLog(
        Organization $organization,
        FundRequest $fundRequest,
        string $noteToken,
    ): void {
        // assert email log exists
        $response = $this->getJson(
            "/api/v1/platform/organizations/$organization->id/fund-requests/$fundRequest->id/email-logs",
            $this->makeApiHeaders($this->makeIdentityProxy($organization->identity)),
        );

        $response->assertSuccessful();
        $data = $response->json('data');

        self::assertCount(3, $data);
        self::assertCount(1, Arr::where($data, fn ($item) => $item['type'] == 'fund_request_denied'));
        self::assertCount(1, Arr::where($data, function ($item) use ($noteToken) {
            return
                $item['type'] == 'fund_request_record_declined' &&
                Str::contains($item['content'], $noteToken);
        }));
    }

    /**
     * @param Organization $organization
     * @param FundRequest $fundRequest
     * @return void
     */
    protected function approveFundRequest(
        Organization $organization,
        FundRequest $fundRequest,
    ): void {
        $response = $this->patch(
            "/api/v1/platform/organizations/$organization->id/fund-requests/$fundRequest->id/approve",
            [],
            $this->makeApiHeaders($this->makeIdentityProxy($organization->identity)),
        );

        $response->assertSuccessful();
    }

    /**
     * @param Organization $organization
     * @param FundRequest $fundRequest
     * @return void
     */
    protected function assertFundRequestApprovedEmailLog(
        Organization $organization,
        FundRequest $fundRequest,
    ): void {
        // assert email log exists
        $response = $this->getJson(
            "/api/v1/platform/organizations/$organization->id/fund-requests/$fundRequest->id/email-logs",
            $this->makeApiHeaders($this->makeIdentityProxy($organization->identity)),
        );

        $response->assertSuccessful();
        $data = $response->json('data');

        self::assertCount(2, $data);
        self::assertCount(1, Arr::where($data, fn ($item) => $item['type'] == 'fund_request_approved'));
    }

    /**
     * @param Organization $organization
     * @param FundRequest $fundRequest
     * @param bool $notify
     * @return void
     */
    protected function disregardFundRequest(
        Organization $organization,
        FundRequest $fundRequest,
        bool $notify,
    ): void {
        $response = $this->patch(
            "/api/v1/platform/organizations/$organization->id/fund-requests/$fundRequest->id/disregard",
            [ 'notify' => $notify ],
            $this->makeApiHeaders($this->makeIdentityProxy($organization->identity)),
        );

        $response->assertSuccessful();
    }

    /**
     * @param Organization $organization
     * @param FundRequest $fundRequest
     * @param bool $notify
     * @return void
     */
    protected function assertFundRequestDisregardedEmailLog(
        Organization $organization,
        FundRequest $fundRequest,
        bool $notify,
    ): void {
        // assert email log exists
        $response = $this->getJson(
            "/api/v1/platform/organizations/$organization->id/fund-requests/$fundRequest->id/email-logs",
            $this->makeApiHeaders($this->makeIdentityProxy($organization->identity)),
        );

        $response->assertSuccessful();
        $data = $response->json('data');

        self::assertCount($notify ? 2 : 1, $data);
        self::assertCount(
            $notify ? 1 : 0,
            Arr::where($data, fn ($item) => $item['type'] == 'fund_request_disregarded'),
        );
    }
}
