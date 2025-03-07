<?php

namespace Tests\Feature;

use App\Models\Voucher;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;
use Tests\Traits\MakesTestFunds;
use Tests\Traits\MakesTestIdentities;
use Tests\Traits\MakesTestOrganizations;
use Tests\Traits\VoucherTestTrait;
use Throwable;

class FundCheckRelationBsnTest extends TestCase
{
    use VoucherTestTrait;
    use DatabaseTransactions;
    use MakesTestFunds;
    use MakesTestOrganizations;
    use MakesTestIdentities;

    /**
     * @throws Throwable
     * @return void
     */
    public function testBsnRelationVoucherReceived(): void
    {
        $voucher = $this->makeFundForCheckTest();
        $requester = $this->makeIdentity($this->makeUniqueEmail(), $voucher->voucher_relation->bsn);

        self::assertEquals($voucher->voucher_relation->bsn, $requester->bsn);

        $response = $this->postJson(
            "/api/v1/platform/funds/$voucher->fund_id/check",
            [],
            $this->makeApiHeaders($this->makeIdentityProxy($requester)),
        );

        $response->assertSuccessful();

        $this->assertEquals(1, $response->json('vouchers'));
        $this->assertEquals($voucher->fresh()->identity_id, $requester->id);
    }

    /**
     * @throws Throwable
     * @return void
     */
    public function testInactiveBsnRelationVoucherNotReceived(): void
    {
        $voucher = $this->makeFundForCheckTest();
        $requester = $this->makeIdentity($this->makeUniqueEmail(), $voucher->voucher_relation->bsn);

        self::assertEquals($voucher->voucher_relation->bsn, $requester->bsn);

        $this->travel(2)->years();
        $requester->setBsnRecord($voucher->voucher_relation->bsn);

        $futureFund = $this->makeTestFund($voucher->fund->organization);

        $response = $this->postJson(
            "/api/v1/platform/funds/$futureFund->id/check",
            [],
            $this->makeApiHeaders($this->makeIdentityProxy($requester)),
        );

        $response->assertSuccessful();

        $this->assertEquals(0, $response->json('vouchers'));
        $this->assertNull($voucher->fresh()->identity_id);
    }

    /**
     * @throws Throwable
     * @return void
     */
    public function testExpiredBsnRelationVoucherNotReceived(): void
    {
        $voucher = $this->makeFundForCheckTest();
        $requester = $this->makeIdentity($this->makeUniqueEmail(), $voucher->voucher_relation->bsn);

        self::assertEquals($voucher->voucher_relation->bsn, $requester->bsn);

        $voucher->update([ 'expire_at' => now()->subDays(5) ]);

        $response = $this->postJson(
            "/api/v1/platform/funds/$voucher->fund_id/check",
            [],
            $this->makeApiHeaders($this->makeIdentityProxy($requester)),
        );

        $response->assertSuccessful();

        $this->assertEquals(0, $response->json('vouchers'));
        $this->assertNull($voucher->fresh()->identity_id);
    }

    /**
     * @throws Throwable
     * @return Voucher
     */
    protected function makeFundForCheckTest(): Voucher
    {
        $sponsorIdentity = $this->makeIdentity($this->makeUniqueEmail());
        $sponsorOrganization = $this->makeTestOrganization($sponsorIdentity);
        $sponsorOrganization->update(['bsn_enabled' => true]);

        $fund = $this->makeTestFund($sponsorOrganization);

        $uploadResponse = $this->postJson(
            "/api/v1/platform/organizations/$sponsorOrganization->id/sponsor/vouchers",
            [
                'fund_id' => $fund->id,
                'assign_by_type' => 'bsn',
                'bsn' => (string) $this->randomFakeBsn(),
                'limit_multiplier' => 1,
                'amount' => 100,
            ],
            $this->makeApiHeaders($this->makeIdentityProxy($sponsorIdentity)),
        );

        $uploadResponse->assertSuccessful();
        $voucher = Voucher::find($uploadResponse->json('data.id'));

        self::assertNotNull($voucher);

        return $voucher;
    }
}
