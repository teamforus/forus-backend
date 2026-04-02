<?php

namespace Tests\Unit;

use App\Models\RecordType;
use App\Traits\DoesTesting;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Tests\CreatesApplication;
use Tests\TestCase;
use Tests\Traits\MakesTestFundRequests;
use Tests\Traits\MakesTestFunds;
use Throwable;

class FundTakenByPartnerTest extends TestCase
{
    use DoesTesting;
    use MakesTestFunds;
    use CreatesApplication;
    use DatabaseTransactions;
    use MakesTestFundRequests;

    /**
     * @throws Throwable
     * @return void
     */
    public function testFundTakenByPartnerPendingFundRequest(): void
    {
        $fund = $this->makeTestFund($this->makeTestOrganization($this->makeIdentity()));
        $identity1 = $this->makeIdentity($this->makeUniqueEmail());
        $identity2 = $this->makeIdentity($this->makeUniqueEmail());

        // make pending fund request and add partner_bsn record
        // and assert that partner (identity2) in this fund is taken by partner with pending fund request
        DB::beginTransaction();
        $fundRequest = $this->setCriteriaAndMakeFundRequest($identity1, $fund, ['children_nth' => 3]);

        $fundRequest->records()->create([
            'record_type_key' => 'partner_bsn',
            'value' => 123456789,
        ]);

        $this->assertFalse($fund->isTakenByPartnerPendingFundRequest($identity2), 'Identity dont have partner with pending fund request');
        $identity2->setBsnRecord(123456789);
        $this->assertTrue($fund->isTakenByPartnerPendingFundRequest($identity2), 'Identity have partner with pending fund request');
        DB::rollBack();

        // add to identity1 partner_bsn record and make fund request - then assert that partner bsn
        // can be found by record and found it's pending fund request
        DB::beginTransaction();
        $identity1
            ->makeRecord(RecordType::where('key', 'partner_bsn')->first(), 123456789)
            ->makeValidationRequest()
            ->approve($fund->organization->identity, $fund->organization);

        $this->setCriteriaAndMakeFundRequest($identity1, $fund, ['children_nth' => 3]);
        $this->assertTrue($fund->isTakenByPartnerPendingFundRequest($identity2), 'Identity have partner with pending fund request');
        DB::rollBack();
    }
}
