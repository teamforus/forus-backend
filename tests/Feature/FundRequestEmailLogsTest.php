<?php

namespace Tests\Feature;

use App\Models\FundRequest;
use App\Models\Organization;
use App\Services\MediaService\Traits\UsesMediaService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;
use Tests\Traits\MakesTestFundRequests;
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
    use MakesTestFundRequests;

    /**
     * @throws \Throwable
     */
    public function testRequestFundEmailLogCreated()
    {
        // create sponsor and requester identities
        $sponsorIdentity = $this->makeIdentity($this->makeUniqueEmail());
        $requesterIdentity = $this->makeIdentity($this->makeUniqueEmail());
        $requesterIdentity->setBsnRecord('123456789');

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
            $this->makeApiHeaders($organization->identity),
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
            $this->makeApiHeaders($organization->identity),
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
            $this->makeApiHeaders($organization->identity),
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
            $this->makeApiHeaders($organization->identity),
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
            $this->makeApiHeaders($organization->identity),
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
