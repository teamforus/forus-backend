<?php

namespace Tests\Feature;

use App\Mail\Vouchers\ProductBoughtProviderBySponsorMail;
use App\Mail\Vouchers\ProductBoughtProviderMail;
use App\Models\Fund;
use App\Models\Organization;
use App\Models\Voucher;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;
use Tests\Traits\MakesTestFunds;
use Tests\Traits\MakesTestOrganizations;
use Tests\Traits\VoucherTestTrait;
use Throwable;

class VoucherEmailTest extends TestCase
{
    use MakesTestFunds;
    use VoucherTestTrait;
    use DatabaseTransactions;
    use MakesTestOrganizations;

    /**
     * @throws \Random\RandomException
     * @throws Throwable
     * @return void
     */
    public function testProductVoucherEmailToProvider(): void
    {
        $startDate = now();

        $organization = $this->makeTestOrganization($this->makeIdentity());
        $fund = $this->makeTestFund($organization);

        $product = $this->makeProviderAndProducts($fund)['approved'][0];
        $provider = $product->organization;

        DB::beginTransaction();
        $this->storeVoucher($fund, [
            'product_id' => $product->id,
            'notify_provider' => true,
        ]);

        $this->assertMailableNotSent($provider->identity->email, ProductBoughtProviderMail::class, $startDate);
        $this->assertMailableSent($provider->identity->email, ProductBoughtProviderBySponsorMail::class, $startDate);
        DB::rollBack();

        DB::beginTransaction();
        $this->storeVoucher($fund, [
            'product_id' => $product->id,
            'notify_provider' => false,
        ]);

        $this->assertMailableNotSent($provider->identity->email, ProductBoughtProviderMail::class, $startDate);
        $this->assertMailableNotSent($provider->identity->email, ProductBoughtProviderBySponsorMail::class, $startDate);
        DB::rollBack();
    }

    /**
     * @param Fund $fund
     * @param array $data
     * @throws \Random\RandomException
     * @return void
     */
    protected function storeVoucher(Fund $fund, array $data): void
    {
        $organization = $fund->organization;

        $data = [
            'fund_id' => $fund->id,
            'assign_by_type' => 'email',
            'email' => $this->makeUniqueEmail(),
            'limit_multiplier' => 1,
            'expire_at' => now()->addDays(30)->format('Y-m-d'),
            'note' => $this->faker()->sentence(),
            'amount' => random_int(1, $fund->getMaxAmountPerVoucher()),
            ...$data,
        ];

        $this->makeVoucherStoreValidateRequest($organization, $data)->assertSuccessful();

        $uploadResponse = $this->makeVoucherStoreRequest($organization, $data);
        $uploadResponse->assertSuccessful();
        $voucher = Voucher::find($uploadResponse->json('data.id'));
        $this->assertNotNull($voucher);
    }

    /**
     * @param Organization $organization
     * @param array $data
     * @return TestResponse
     */
    protected function makeVoucherStoreValidateRequest(Organization $organization, array $data): TestResponse
    {
        return $this->postJson(
            "/api/v1/platform/organizations/$organization->id/sponsor/vouchers/validate",
            $data,
            $this->makeApiHeaders($organization->identity),
        );
    }

    /**
     * @param Organization $organization
     * @param array $data
     * @return TestResponse
     */
    protected function makeVoucherStoreRequest(Organization $organization, array $data): TestResponse
    {
        return $this->postJson(
            "/api/v1/platform/organizations/$organization->id/sponsor/vouchers",
            $data,
            $this->makeApiHeaders($organization->identity),
        );
    }
}
