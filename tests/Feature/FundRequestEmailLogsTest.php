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
use Throwable;

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
     * @throws Throwable
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
        $this->requestFundRequestClarification($organization, $fundRequest);
        $this->assertFundRequestClarificationEmailLog($organization, $fundRequest);
        DB::rollBack();

        DB::beginTransaction();
        $this->apiFundRequestApproveRequest($fundRequest, $organization->employees[0])->assertSuccessful();
        $this->assertFundRequestApprovedEmailLog($organization, $fundRequest);
        DB::rollBack();

        DB::beginTransaction();
        $this->apiFundRequestDisregardRequest($fundRequest, ['notify' => true], $organization->employees[0])->assertSuccessful();
        $this->assertFundRequestDisregardedEmailLog($organization, $fundRequest, true);
        DB::rollBack();

        DB::beginTransaction();
        $this->apiFundRequestDisregardRequest($fundRequest, ['notify' => false], $organization->employees[0])->assertSuccessful();
        $this->assertFundRequestDisregardedEmailLog($organization, $fundRequest, false);
        DB::rollBack();
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
        $response = $this->apiGetOrganizationEmailLogsRequest($organization, ['fund_request_id' => $fundRequest->id]);
        $data = $response->assertSuccessful()->json('data');

        self::assertCount(2, $data);
        self::assertCount(1, Arr::where($data, fn ($item) => $item['type'] == 'fund_request_approved'));
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
        $data = $this
            ->apiGetOrganizationEmailLogsRequest($organization, ['fund_request_id' => $fundRequest->id])
            ->assertSuccessful()
            ->json('data');

        self::assertCount($notify ? 2 : 1, $data);
        self::assertCount(
            $notify ? 1 : 0,
            Arr::where($data, fn ($item) => $item['type'] == 'fund_request_disregarded'),
        );
    }
}
