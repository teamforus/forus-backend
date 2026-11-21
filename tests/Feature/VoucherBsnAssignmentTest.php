<?php

namespace Tests\Feature;

use App\Models\Voucher;
use App\Models\VoucherRelation;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;
use Tests\Traits\MakesTestFunds;
use Tests\Traits\MakesTestOrganizations;
use Tests\Traits\MakesTestVouchers;
use Throwable;

class VoucherBsnAssignmentTest extends TestCase
{
    use DatabaseTransactions;
    use MakesTestFunds;
    use MakesTestOrganizations;
    use MakesTestVouchers;

    /**
     * @throws Throwable
     * @return void
     */
    public function testSponsorVoucherNormalizesBsnAndAssignsExistingIdentity(): void
    {
        $manager = $this->makeIdentity($this->makeUniqueEmail());
        $organization = $this->makeTestOrganization($manager, ['bsn_enabled' => true]);
        $fund = $this->makeTestFund($organization);
        $bsn = $this->makeUniqueBsn(leadingZero: true);
        $identity = $this->makeIdentity($this->makeUniqueEmail(), $bsn);

        $response = $this->apiMakeVoucherAsSponsorRequest($organization, $fund, [
            'assign_by_type' => 'bsn',
            'bsn' => substr($bsn, 1),
            'amount' => '125.50',
        ], $manager)->assertSuccessful();

        $voucher = Voucher::findOrFail($response->json('data.id'));

        $this->assertSame($bsn, $voucher->voucher_relation->bsn);
        $this->assertSame($identity->id, $voucher->identity_id);
        $this->assertEquals(125.50, $voucher->amount);
        $this->assertTrue($voucher->granted);
    }

    /**
     * @throws Throwable
     * @return void
     */
    public function testVoucherWithNormalizedBsnCanBeAssignedAfterIdentityCreation(): void
    {
        $manager = $this->makeIdentity($this->makeUniqueEmail());
        $organization = $this->makeTestOrganization($manager, ['bsn_enabled' => true]);
        $fund = $this->makeTestFund($organization);
        $bsn = $this->makeUniqueBsn(leadingZero: true);

        $response = $this->apiMakeVoucherAsSponsorRequest($organization, $fund, [
            'assign_by_type' => 'bsn',
            'bsn' => substr($bsn, 1),
            'amount' => '50',
            'activate' => true,
        ], $manager)->assertSuccessful();

        $voucher = Voucher::findOrFail($response->json('data.id'));
        $unrelated = $this->makeTestVoucher($fund);
        $unrelated->setBsnRelation($this->makeUniqueBsn(), VoucherRelation::REPORT_TYPE_USER);

        $this->assertNull($voucher->identity_id);
        $this->assertSame($bsn, $voucher->voucher_relation->bsn);

        $identity = $this->makeIdentity($this->makeUniqueEmail(), $bsn);

        $this->assertSame(1, Voucher::assignAvailableToIdentityByBsn($identity));
        $this->assertSame($identity->id, $voucher->refresh()->identity_id);
        $this->assertSame($bsn, $voucher->voucher_relation->bsn);
        $this->assertTrue($voucher->granted);
        $this->assertNull($unrelated->refresh()->identity_id);
    }
}
