<?php

namespace Tests\Feature;

use App\Models\FundRequest;
use App\Models\Organization;
use App\Services\MediaService\Traits\UsesMediaService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
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
        $sponsorIdentity = $this->makeIdentity(email: $this->makeUniqueEmail());
        $requesterIdentity = $this->makeIdentity(email: $this->makeUniqueEmail(), bsn: 123456789);

        // create the organization and fund
        $organization = $this->makeTestOrganization($sponsorIdentity);
        $fund = $this->makeTestFund($organization);

        $records = [[
            'fund_criterion_id' => $fund->criteria[0]?->id,
            'value' => 5,
            'files' => [],
        ]];

        // create fund request and assert email log created
        $response = $this->makeFundRequest($requesterIdentity, $fund, $records, false);
        $response->assertSuccessful();
        /** @var FundRequest $fundRequest */
        $fundRequest = FundRequest::find($response->json('data.id'));
        $this->assertNotNull($fundRequest);
        $this->assertFundRequestCreateEmailLog($organization, $fundRequest);

        $fundRequest->assignEmployee($organization->findEmployee($sponsorIdentity));

        DB::beginTransaction();
        $questionToken = $this->requestFundRequestClarification($organization, $fundRequest);
        $this->assertFundRequestClarificationEmailLog($organization, $fundRequest, $questionToken);
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
