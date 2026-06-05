<?php

namespace Feature;

use App\Models\FundProductLimit;
use App\Models\Product;
use App\Models\ProductReservation;
use App\Models\Voucher;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;
use Tests\Traits\MakesProductReservations;
use Tests\Traits\MakesTestFundProductLimits;
use Tests\Traits\MakesTestFunds;
use Throwable;

class FundProductLimitTest extends TestCase
{
    use MakesTestFunds;
    use DatabaseTransactions;
    use MakesProductReservations;
    use MakesTestFundProductLimits;

    /**
     * Make 3 products (A,B,C)
     * Assert reservation is available for all
     * Create fund product limit type SELECTED and two products (A,B) with limit 1
     * Reserve product A
     * Assert product B not available to reserve
     * Assert product C is available as it not in limit
     * Assert product A can still be reserved (if voucher has limit_multiplier = 2).
     * @throws Throwable
     * @return void
     */
    public function testFundProductLimitCaseTypeSelected(): void
    {
        $organization = $this->makeTestOrganization($this->makeIdentity());
        $fund = $this->makeTestFund($organization);

        $voucher = $fund->makeVoucher($fund->organization->identity, [
            'state' => Voucher::STATE_ACTIVE,
        ], amount: 10000, limitMultiplier: 2);

        $provider = $this->makeTestProviderOrganization($this->makeIdentity());
        $productA = $this->createProductForReservation($provider, [$fund]);
        $productB = $this->createProductForReservation($provider, [$fund]);
        $productC = $this->createProductForReservation($provider, [$fund]);

        DB::beginTransaction();
        $this->createReservation($voucher, $productA);
        $this->createReservation($voucher, $productB);
        $this->createReservation($voucher, $productC);
        DB::rollBack();

        $this->makeFundProductLimit($fund, FundProductLimit::TYPE_SELECTED, 1, [$productA->id, $productB->id]);

        $this->createReservation($voucher, $productA);
        $this->createReservation($voucher, $productB, false);
        $this->createReservation($voucher, $productC);
        $this->createReservation($voucher, $productA);
    }

    /**
     * Make 4 products (A,B,C,D)
     * Create fund product limit type ALL and one product (A) with limit 2
     * Reserve product A (as it excluded)
     * Assert product B available to reserve and reserve it
     * Assert product C available to reserve and reserve it
     * Assert product D is not available to reserve.
     * @return void
     */
    public function testFundProductLimitCaseTypeAll(): void
    {
        $organization = $this->makeTestOrganization($this->makeIdentity());
        $fund = $this->makeTestFund($organization);

        $voucher = $fund->makeVoucher($fund->organization->identity, [
            'state' => Voucher::STATE_ACTIVE,
        ], amount: 10000);

        $provider = $this->makeTestProviderOrganization($this->makeIdentity());
        $productA = $this->createProductForReservation($provider, [$fund]);
        $productB = $this->createProductForReservation($provider, [$fund]);
        $productC = $this->createProductForReservation($provider, [$fund]);
        $productD = $this->createProductForReservation($provider, [$fund]);

        $this->makeFundProductLimit($fund, FundProductLimit::TYPE_ALL, 2, [$productA->id]);

        $this->createReservation($voucher, $productA);
        $this->createReservation($voucher, $productB);
        $this->createReservation($voucher, $productC);
        $this->createReservation($voucher, $productD, false);
    }

    /**
     * Make 4 products (A,B,C,D)
     * Create fund product limit type SELECTED and products (A,B,D) with limit 2
     * Create fund product limit type SELECTED and products (A,B,C) with limit 1
     * Reserve product A
     * Assert product B is not available to reserve (as it’s part of limit #2)
     * Assert product C is not available to reserve (as it’s part of limit #2)
     * Assert product D is available to reserve (as part of limits #1).
     * @return void
     */
    public function testFundProductLimitCaseTwoLimits(): void
    {
        $organization = $this->makeTestOrganization($this->makeIdentity());
        $fund = $this->makeTestFund($organization);

        $voucher = $fund->makeVoucher($fund->organization->identity, [
            'state' => Voucher::STATE_ACTIVE,
        ], amount: 10000);

        $provider = $this->makeTestProviderOrganization($this->makeIdentity());
        $productA = $this->createProductForReservation($provider, [$fund]);
        $productB = $this->createProductForReservation($provider, [$fund]);
        $productC = $this->createProductForReservation($provider, [$fund]);
        $productD = $this->createProductForReservation($provider, [$fund]);

        $this->makeFundProductLimit($fund, FundProductLimit::TYPE_SELECTED, 2, [$productA->id, $productB->id, $productD->id]);
        $this->makeFundProductLimit($fund, FundProductLimit::TYPE_SELECTED, 1, [$productA->id, $productB->id, $productC->id]);

        $this->createReservation($voucher, $productA);
        $this->createReservation($voucher, $productB, false);
        $this->createReservation($voucher, $productC, false);
        $this->createReservation($voucher, $productD);
    }

    /**
     * Make 4 products (A,B,C,D)
     * Create fund product limit type ALL and products (A,B) with limit 2
     * Create fund product limit type SELECTED and products (A,B,C) with limit 1
     * Reserve product A
     * Assert product B is not available to reserve (as it’s part of limit #2)
     * Assert product C is not available to reserve (as it’s part of limit #2)
     * Assert product D is available to reserve (as part of limits #1).
     * @return void
     */
    public function testFundProductLimitCaseTwoDifferentTypes(): void
    {
        $organization = $this->makeTestOrganization($this->makeIdentity());
        $fund = $this->makeTestFund($organization);

        $voucher = $fund->makeVoucher($fund->organization->identity, [
            'state' => Voucher::STATE_ACTIVE,
        ], amount: 10000);

        $provider = $this->makeTestProviderOrganization($this->makeIdentity());
        $productA = $this->createProductForReservation($provider, [$fund]);
        $productB = $this->createProductForReservation($provider, [$fund]);
        $productC = $this->createProductForReservation($provider, [$fund]);
        $productD = $this->createProductForReservation($provider, [$fund]);

        $this->makeFundProductLimit($fund, FundProductLimit::TYPE_ALL, 2, [$productA->id, $productB->id]);
        $this->makeFundProductLimit($fund, FundProductLimit::TYPE_SELECTED, 1, [$productA->id, $productB->id, $productC->id]);

        $this->createReservation($voucher, $productA);
        $this->createReservation($voucher, $productB, false);
        $this->createReservation($voucher, $productC, false);
        $this->createReservation($voucher, $productD);
    }

    /**
     * Make 3 products (A,B,C)
     * Create fund product limit type SELECTED and two products (A,B) with limit 1
     * Reserve product A, assert that product B not available to reserve
     * Cancel Reservation for product A
     * Assert product B is available to reserve and reserve it
     * Assert product C is available as it not in limit.
     * @throws Throwable
     * @return void
     */
    public function testFundProductLimitCaseCanceledReservation(): void
    {
        $organization = $this->makeTestOrganization($this->makeIdentity());
        $fund = $this->makeTestFund($organization);

        $voucher = $fund->makeVoucher($fund->organization->identity, [
            'state' => Voucher::STATE_ACTIVE,
        ], amount: 10000);

        $provider = $this->makeTestProviderOrganization($this->makeIdentity());
        $productA = $this->createProductForReservation($provider, [$fund]);
        $productB = $this->createProductForReservation($provider, [$fund]);
        $productC = $this->createProductForReservation($provider, [$fund]);

        $this->makeFundProductLimit($fund, FundProductLimit::TYPE_SELECTED, 1, [$productA->id, $productB->id]);

        $reservationA = $this->createReservation($voucher, $productA);
        $this->createReservation($voucher, $productB, false);

        $reservationA->rejectOrCancelProvider($organization->employees()->first());
        $this->createReservation($voucher, $productB);
        $this->createReservation($voucher, $productC);
    }

    /**
     * Make 3 products (A,B,C)
     * Create fund product limit type SELECTED and two products (A,B) with limit 1
     * Create fund product limit type SELECTED and two products (A,C) with limit 1
     * Reserve product A
     * Assert product B not available to reserve (as part of limit 1)
     * Assert product C not available to reserve (as part of limit 2)
     * Deactivate limit 1
     * Assert product B is available to reserve.
     * @return void
     */
    public function testFundProductLimitCaseLimitDeactivate(): void
    {
        $organization = $this->makeTestOrganization($this->makeIdentity());
        $fund = $this->makeTestFund($organization);

        $voucher = $fund->makeVoucher($fund->organization->identity, [
            'state' => Voucher::STATE_ACTIVE,
        ], amount: 10000);

        $provider = $this->makeTestProviderOrganization($this->makeIdentity());
        $productA = $this->createProductForReservation($provider, [$fund]);
        $productB = $this->createProductForReservation($provider, [$fund]);
        $productC = $this->createProductForReservation($provider, [$fund]);

        $limit1 = $this->makeFundProductLimit($fund, FundProductLimit::TYPE_SELECTED, 1, [$productA->id, $productB->id]);
        $this->makeFundProductLimit($fund, FundProductLimit::TYPE_SELECTED, 1, [$productA->id, $productC->id]);

        $this->createReservation($voucher, $productA);
        $this->createReservation($voucher, $productB, false);
        $this->createReservation($voucher, $productC, false);

        $limit1->update(['state' => FundProductLimit::STATE_INACTIVE]);

        $this->createReservation($voucher, $productB);
    }

    /**
     * @param Voucher $voucher
     * @param Product $product
     * @param bool $assertCreated
     * @return ProductReservation|null
     */
    public function createReservation(Voucher $voucher, Product $product, bool $assertCreated = true): ?ProductReservation
    {
        $fields = [
            'first_name' => $this->faker->firstName(),
            'last_name' => $this->faker->lastName(),
        ];

        $response = $this->makeReservationStoreRequest($voucher, $product, $fields);

        if ($assertCreated) {
            $response->assertSuccessful();

            $reservation = ProductReservation::find($response->json('data.id'));
            $this->assertNotNull($reservation);

            return $reservation;
        }

        // if $assertCreated = false we assert validation error for 'product_id'
        // because limits must be reached and validation for this product fail
        $response->assertJsonValidationErrorFor('product_id');

        return null;
    }
}
