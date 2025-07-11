<?php

namespace Tests\Unit;

use App\Http\Requests\BaseFormRequest;
use App\Models\Voucher;
use App\Traits\DoesTesting;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\CreatesApplication;
use Tests\TestCase;
use Tests\Traits\MakesTestFunds;
use Tests\Traits\MakesTestOrganizations;
use Tests\Traits\MakesTestProducts;
use Tests\Traits\MakesTestVouchers;

class VoucherBalanceTest extends TestCase
{
    use DoesTesting;
    use MakesTestFunds;
    use MakesTestProducts;
    use MakesTestVouchers;
    use CreatesApplication;
    use DatabaseTransactions;
    use MakesTestOrganizations;

    /**
     * @return void
     * @throws \Throwable
     */
    public function testVoucherBalance(): void
    {
        $voucherAmount = 100;
        $productPrice = 15;

        $organization = $this->makeTestOrganization($this->makeIdentity());
        $fund = $this->makeTestFund($organization);
        $voucher = $this->makeTestVoucher($fund, identity: $this->makeIdentity(), amount: $voucherAmount);

        $provider = $this->makeTestOrganization($this->makeIdentity());
        $product = $this->makeTestProduct($provider, price: $productPrice);

        $request = new BaseFormRequest();
        $request->query->add([
            'source' => 'all',
            'type' => 'fund_voucher',
            'amount_available_min' => 10,
        ]);

        $query = Voucher::searchSponsorQuery($request, $organization, $fund)->where('id', $voucher->id);

        // initial voucher amount
        $voucherItem = (clone $query)->first();
        $this->assertNotNull($voucherItem);
        $this->assertEquals($voucherAmount, $voucherItem->balance);

        // pending reservation affects the balance
        $reservation = $voucher->reserveProduct($product, $provider->employees[0]);
        $voucherItem = (clone $query)->first();
        $this->assertNotNull($voucherItem);
        $this->assertEquals($voucherAmount - $productPrice, $voucherItem->balance);

        // rejected reservation doesn't affect the balance
        $reservation->rejectOrCancelProvider($provider->employees[0]);
        $voucherItem = (clone $query)->first();
        $this->assertNotNull($voucherItem);
        $this->assertEquals($voucherAmount, $voucherItem->balance);
    }
}
