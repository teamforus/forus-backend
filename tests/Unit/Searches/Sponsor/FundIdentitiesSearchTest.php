<?php

namespace Tests\Unit\Searches\Sponsor;

use App\Models\Fund;
use App\Searches\Sponsor\FundIdentitiesSearch;
use App\Traits\DoesTesting;
use Illuminate\Support\Carbon;
use Tests\Traits\MakesTestFunds;
use Tests\Traits\MakesTestOrganizations;
use Tests\Unit\Searches\SearchTestCase;
use Throwable;

class FundIdentitiesSearchTest extends SearchTestCase
{
    use DoesTesting;
    use MakesTestFunds;
    use MakesTestOrganizations;

    /**
     * @return void
     */
    public function testQueryBuilds(): void
    {
        $identity = $this->makeIdentity();
        $organization = $this->makeTestOrganization($identity);
        $fund = $this->makeTestFund($organization);

        $search = new FundIdentitiesSearch([], $fund);

        $this->assertQueryBuilds($search->query());
    }

    /**
     * @return void
     */
    public function testFiltersByQuery(): void
    {
        $emailPart1 = 'f_anywhere_not_used_email';
        $emailPart2 = 's_not_used_anywhere_email';

        $organization = $this->makeTestOrganization($this->makeIdentity());
        $fund = $this->makeTestFund($organization);

        $identity1 = $this->makeIdentity($this->makeUniqueEmail($emailPart1));
        $identity2 = $this->makeIdentity($this->makeUniqueEmail($emailPart2));

        $fund->makeVoucher($identity1);
        $fund->makeVoucher($identity2);

        $this->assertSearchIds(['q' => $emailPart1], [$identity1->id], $fund);
        $this->assertSearchIds(['q' => $emailPart2], [$identity2->id], $fund);
    }

    /**
     * @return void
     */
    public function testFiltersByBalance(): void
    {
        $organization = $this->makeTestOrganization($this->makeIdentity());
        $employee = $organization->employees()->first();
        $fund = $this->makeTestFund($organization);

        $identity1 = $this->makeIdentity($this->makeUniqueEmail());
        $identity2 = $this->makeIdentity($this->makeUniqueEmail());

        $fund->makeVoucher($identity1);
        $voucher2 = $fund->makeVoucher($identity2);

        $voucher2->makeDirectPayment($this->faker->iban(), $this->faker->name(), $employee);

        $this->assertSearchIds(['target' => 'all'], [$identity1->id, $identity2->id], $fund);
        $this->assertSearchIds(['target' => 'has_balance'], [$identity1->id], $fund);
    }

    /**
     * @throws Throwable
     * @return void
     */
    public function testFiltersByActiveVouchers(): void
    {
        $organization = $this->makeTestOrganization($this->makeIdentity());
        $fund = $this->makeTestFund($organization);

        $identity1 = $this->makeIdentity($this->makeUniqueEmail());
        $identity2 = $this->makeIdentity($this->makeUniqueEmail());

        $voucher1 = $fund->makeVoucher($identity1);
        $voucher2 = $fund->makeVoucher($identity2);

        // assert all identities with active and not expired vouchers are visible
        $this->assertSearchIds([], [$identity1->id, $identity2->id], $fund);

        // expire first voucher and assert only second identity is visible
        $voucher1->update(['expire_at' => Carbon::now()->subDay()]);
        $this->assertSearchIds([], [$identity2->id], $fund);

        // deactivate second voucher and assert no identities are visible
        $voucher2->deactivate();
        $this->assertSearchIds([], [], $fund);
    }

    /**
     * @return void
     */
    public function testFiltersByHasEmail(): void
    {
        $organization = $this->makeTestOrganization($this->makeIdentity());
        $fund = $this->makeTestFund($organization);

        $identityHasEmail = $this->makeIdentity($this->makeUniqueEmail());
        $identityWithoutEmail = $this->makeIdentity();

        $fund->makeVoucher($identityHasEmail);
        $fund->makeVoucher($identityWithoutEmail);

        $this->assertSearchIds(['has_email' => true], [$identityHasEmail->id], $fund);
        $this->assertSearchIds(['has_email' => false], [$identityWithoutEmail->id], $fund);
    }

    /**
     * @param array $filters
     * @param Fund $fund
     * @return FundIdentitiesSearch
     */
    private function makeSearch(array $filters, Fund $fund): FundIdentitiesSearch
    {
        return new FundIdentitiesSearch($filters, $fund);
    }

    /**
     * @param array $filters
     * @param array $expectedIds
     * @param Fund $fund
     * @return void
     */
    private function assertSearchIds(array $filters, array $expectedIds, Fund $fund): void
    {
        $expected = collect($expectedIds)->sort()->values()->toArray();
        $search = $this->makeSearch($filters, $fund);
        $actual = collect($search->query()->pluck('id')->toArray())->sort()->values()->toArray();

        $this->assertSame($expected, $actual);
    }
}
