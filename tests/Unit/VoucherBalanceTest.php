<?php

namespace Tests\Unit;

use App\Models\Voucher;
use App\Searches\VouchersSearch;
use App\Traits\DoesTesting;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\CreatesApplication;
use Tests\TestCase;
use Tests\Traits\MakesTestFunds;
use Tests\Traits\MakesTestOrganizations;
use Tests\Traits\MakesTestProducts;
use Tests\Traits\MakesTestVouchers;
use Throwable;

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
     * @throws Throwable
     * @return void
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

        $search = new VouchersSearch([
            'source' => 'all',
            'type' => 'fund_voucher',
            'amount_available_min' => 10,
        ], Voucher::query());

        $query = $search->searchSponsor($organization, $fund)->where('id', $voucher->id);

        // initial voucher amount
        $voucherItem = (clone $query)->first();
        $this->assertNotNull($voucherItem);
        $this->assertEquals($voucherAmount, $voucherItem->getAttribute('balance'));

        // pending reservation affects the balance
        $reservation = $voucher->reserveProduct($product, $provider->employees[0]);
        $voucherItem = (clone $query)->first();
        $this->assertNotNull($voucherItem);
        $this->assertEquals($voucherAmount - $productPrice, $voucherItem->getAttribute('balance'));

        // rejected reservation doesn't affect the balance
        $reservation->rejectOrCancelProvider($provider->employees[0]);
        $voucherItem = (clone $query)->first();
        $this->assertNotNull($voucherItem);
        $this->assertEquals($voucherAmount, $voucherItem->getAttribute('balance'));
    }
}
