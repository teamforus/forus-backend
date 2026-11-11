<?php

namespace Tests\Feature;

use App\Models\Fund;
use App\Models\FundProductLimit;
use App\Models\FundProvider;
use App\Models\Permission;
use App\Models\Product;
use App\Models\ProductReservation;
use App\Models\Role;
use App\Models\Voucher;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
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
     * @return void
     */
    public function testShowFundProductLimitEndpointAuthorization(): void
    {
        $organization = $this->makeTestOrganization($this->makeIdentity(), [
            'allow_fund_product_limits' => true,
        ]);
        $fund = $this->makeTestFund($organization);
        $fundProductLimit = $this->makeFundProductLimit($fund);

        $otherOrganization = $this->makeTestOrganization($this->makeIdentity(), [
            'allow_fund_product_limits' => true,
        ]);
        $otherFund = $this->makeTestFund($otherOrganization);
        $otherFundProductLimit = $this->makeFundProductLimit($otherFund);

        $this->apiGetFundProductLimitRequest($organization, $fundProductLimit, $organization->identity)
            ->assertSuccessful()
            ->assertJsonPath('data.id', $fundProductLimit->id);

        $this->apiGetFundProductLimitRequest($organization, $otherFundProductLimit, $organization->identity)
            ->assertForbidden();
    }

    /**
     * @return void
     */
    public function testIndexFundFilterAllowsManageProvidersPermission(): void
    {
        $organization = $this->makeTestOrganization($this->makeIdentity(), [
            'allow_fund_product_limits' => true,
        ]);
        $fund = $this->makeTestFund($organization);
        $fundProductLimit = $this->makeFundProductLimit($fund);

        $role = Role::create(['key' => token_generator()->generate(32)]);
        $role->attachPermissions([Permission::MANAGE_PROVIDERS]);

        $employeeIdentity = $this->makeIdentity($this->makeUniqueEmail());
        $organization->addEmployee($employeeIdentity, [$role->id]);

        $this->apiGetFundProductLimitsRequest($organization, ['fund_id' => $fund->id], $employeeIdentity)
            ->assertSuccessful()
            ->assertJsonPath('data.0.id', $fundProductLimit->id);
    }

    /**
     * @return void
     */
    public function testIndexOnlyReturnsAvailableFundProductLimits(): void
    {
        $organization = $this->makeTestOrganization($this->makeIdentity(), [
            'allow_fund_product_limits' => true,
        ]);

        $fund = $this->makeTestFund($organization);
        $fundProductLimit = $this->makeFundProductLimit($fund);

        $externalFund = $this->makeTestFund($organization, ['external' => true]);
        $this->makeFundProductLimit($externalFund);

        $unconfiguredFund = $this->makeTestFund($organization, [], ['is_configured' => false]);
        $this->makeFundProductLimit($unconfiguredFund);

        $closedFund = $this->makeTestFund($organization);
        $closedFund->update(['state' => Fund::STATE_CLOSED]);
        $this->makeFundProductLimit($closedFund);

        $this->apiGetFundProductLimitsRequest($organization, [], $organization->identity)
            ->assertSuccessful()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $fundProductLimit->id);
    }

    /**
     * @return void
     */
    public function testStoreValidatesProductsForSelectedFund(): void
    {
        $organization = $this->makeTestOrganization($this->makeIdentity(), [
            'allow_fund_product_limits' => true,
        ]);

        $fund = $this->makeTestFund($organization);
        $otherFund = $this->makeTestFund($organization);

        $provider = $this->makeTestProviderOrganization($this->makeIdentity());
        $product = $this->createProductForReservation($provider, [$otherFund]);

        $this->apiMakeFundProductLimitRequest(
            $organization,
            [
                'fund_id' => $fund->id,
                'state' => FundProductLimit::STATE_ACTIVE,
                'type' => FundProductLimit::SCOPE_ONLY_SELECTED,
                'limit' => 1,
                'products' => [$product->id],
            ],
            $organization->identity,
        )->assertJsonValidationErrorFor('products.0');
    }

    /**
     * @return void
     */
    public function testStoreAcceptsOnlyApprovedProductsForSelectedFund(): void
    {
        $organization = $this->makeTestOrganization($this->makeIdentity(), [
            'allow_fund_product_limits' => true,
        ]);
        $fund = $this->makeTestFund($organization);

        $provider = $this->makeTestProviderOrganization($this->makeIdentity());
        $product = $this->makeTestProduct($provider);

        $product->fund_providers()->firstOrCreate([
            'organization_id' => $provider->id,
            'fund_id' => $fund->id,
            'state' => FundProvider::STATE_PENDING,
            'allow_budget' => true,
            'allow_products' => true,
        ]);

        $this->apiMakeFundProductLimitRequest(
            $organization,
            [
                'fund_id' => $fund->id,
                'state' => FundProductLimit::STATE_ACTIVE,
                'type' => FundProductLimit::SCOPE_ONLY_SELECTED,
                'limit' => 1,
                'products' => [$product->id],
            ],
            $organization->identity,
        )->assertJsonValidationErrorFor('products.0');
    }

    /**
     * @return void
     */
    public function testStorePersistsFundProductLimitProducts(): void
    {
        $organization = $this->makeTestOrganization($this->makeIdentity(), [
            'allow_fund_product_limits' => true,
        ]);
        $fund = $this->makeTestFund($organization);

        $provider = $this->makeTestProviderOrganization($this->makeIdentity());
        $product = $this->createProductForReservation($provider, [$fund]);

        $response = $this->apiMakeFundProductLimitRequest(
            $organization,
            [
                'fund_id' => $fund->id,
                'state' => FundProductLimit::STATE_ACTIVE,
                'type' => FundProductLimit::SCOPE_ONLY_SELECTED,
                'limit' => 2,
                'products' => [$product->id],
            ],
            $organization->identity,
        )
            ->assertSuccessful()
            ->assertJsonPath('data.fund_id', $fund->id)
            ->assertJsonPath('data.products.0.id', $product->id);

        $fundProductLimit = FundProductLimit::find($response->json('data.id'));

        $this->assertNotNull($fundProductLimit);
        $this->assertSame($fund->id, $fundProductLimit->fund_id);
        $this->assertSame(FundProductLimit::STATE_ACTIVE, $fundProductLimit->state);
        $this->assertSame(FundProductLimit::SCOPE_ONLY_SELECTED, $fundProductLimit->type);
        $this->assertEquals(2, $fundProductLimit->limit);
        $this->assertSame([$product->id], $fundProductLimit->products()->pluck('products.id')->all());
    }

    /**
     * @return void
     */
    public function testUpdatePersistsFundProductLimitProducts(): void
    {
        $organization = $this->makeTestOrganization($this->makeIdentity(), [
            'allow_fund_product_limits' => true,
        ]);
        $fund = $this->makeTestFund($organization);

        $provider = $this->makeTestProviderOrganization($this->makeIdentity());
        $productA = $this->createProductForReservation($provider, [$fund]);
        $productB = $this->createProductForReservation($provider, [$fund]);
        $fundProductLimit = $this->makeFundProductLimit(
            $fund,
            FundProductLimit::SCOPE_ONLY_SELECTED,
            1,
            [$productA->id],
        );

        $this->apiUpdateFundProductLimitRequest(
            $organization,
            $fundProductLimit,
            [
                'fund_id' => $fund->id,
                'state' => FundProductLimit::STATE_INACTIVE,
                'type' => FundProductLimit::SCOPE_ONLY_SELECTED,
                'limit' => 2,
                'products' => [$productB->id],
            ],
            $organization->identity,
        )
            ->assertSuccessful()
            ->assertJsonPath('data.state', FundProductLimit::STATE_INACTIVE)
            ->assertJsonPath('data.products.0.id', $productB->id);

        $fundProductLimit->refresh();

        $this->assertSame($fund->id, $fundProductLimit->fund_id);
        $this->assertSame(FundProductLimit::STATE_INACTIVE, $fundProductLimit->state);
        $this->assertSame(FundProductLimit::SCOPE_ONLY_SELECTED, $fundProductLimit->type);
        $this->assertEquals(2, $fundProductLimit->limit);
        $this->assertSame([$productB->id], $fundProductLimit->products()->pluck('products.id')->all());
    }

    /**
     * @return void
     */
    public function testUnavailableFundProductLimitsAreDenied(): void
    {
        $organization = $this->makeTestOrganization($this->makeIdentity(), [
            'allow_fund_product_limits' => true,
        ]);

        $externalFund = $this->makeTestFund($organization, ['external' => true]);
        $unconfiguredFund = $this->makeTestFund($organization, [], ['is_configured' => false]);
        $closedFund = $this->makeTestFund($organization);
        $closedFund->update(['state' => Fund::STATE_CLOSED]);

        foreach ([$externalFund, $unconfiguredFund, $closedFund] as $fund) {
            $fundProductLimit = $this->makeFundProductLimit($fund);

            $this->apiGetFundProductLimitRequest($organization, $fundProductLimit, $organization->identity)
                ->assertForbidden();
            $this->apiUpdateFundProductLimitRequest($organization, $fundProductLimit, [
                'fund_id' => $fund->id,
                'state' => FundProductLimit::STATE_ACTIVE,
                'type' => FundProductLimit::SCOPE_ALL_EXCEPT_SELECTED,
                'limit' => 1,
                'products' => [],
            ], $organization->identity)->assertForbidden();
            $this->apiDeleteFundProductLimitRequest($organization, $fundProductLimit, $organization->identity)
                ->assertForbidden();

            $this->assertNotNull($fundProductLimit->fresh());
        }
    }

    /**
     * @return void
     */
    public function testFundProductLimitCountsReservationsWithinFundOnly(): void
    {
        $organization = $this->makeTestOrganization($this->makeIdentity());
        $fundA = $this->makeTestFund($organization);
        $fundB = $this->makeTestFund($organization);

        $voucherA = $fundA->makeVoucher($organization->identity, [
            'state' => Voucher::STATE_ACTIVE,
        ], amount: 10000);
        $voucherB = $fundB->makeVoucher($organization->identity, [
            'state' => Voucher::STATE_ACTIVE,
        ], amount: 10000);

        $provider = $this->makeTestProviderOrganization($this->makeIdentity());
        $productA = $this->createProductForReservation($provider, [$fundA]);
        $productB = $this->createProductForReservation($provider, [$fundA]);
        $otherFundProduct = $this->createProductForReservation($provider, [$fundB]);

        $this->makeFundProductLimit($fundA, FundProductLimit::SCOPE_ALL_EXCEPT_SELECTED, 1);

        $this->createReservation($voucherB, $otherFundProduct);
        $this->createReservation($voucherA, $productA);
        $this->createReservation($voucherA, $productB, false);
    }

    /**
     * Make 3 products (A,B,C)
     * Assert reservation is available for all
     * Create fund product limit scope ONLY_SELECTED and two products (A,B) with limit 1
     * Reserve product A
     * Assert product B not available to reserve
     * Assert product C is available as it not in limit
     * Assert product A can still be reserved (if voucher has limit_multiplier = 2).
     * @throws Throwable
     * @return void
     */
    public function testFundProductLimitCaseScopeOnlySelected(): void
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

        $this->makeFundProductLimit($fund, FundProductLimit::SCOPE_ONLY_SELECTED, 1, [$productA->id, $productB->id]);

        $this->createReservation($voucher, $productA);
        $this->createReservation($voucher, $productB, false);
        $this->createReservation($voucher, $productC);
        $this->createReservation($voucher, $productA);
    }

    /**
     * The reservation lock checks the fund product limits again inside the transaction (the request validation can
     * be passed by two reservations at the same time).
     * Create fund product limit scope ONLY_SELECTED with products (A,B) and limit 1, reserve product A
     * Assert product B is refused inside the lock and the callback isn't called
     * Assert product A (reserved already) and product C (not in the limit) are allowed.
     * @throws Throwable
     * @return void
     */
    public function testReservationLockRefusesReachedFundProductLimit(): void
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

        $this->makeFundProductLimit($fund, FundProductLimit::SCOPE_ONLY_SELECTED, 1, [$productA->id, $productB->id]);
        $this->createReservation($voucher, $productA);

        $callbackCalled = false;

        try {
            $voucher->transactionWithReservationLock($productB, function () use (&$callbackCalled) {
                $callbackCalled = true;
            });

            $this->fail('The reservation of a product with a reached fund product limit is not refused.');
        } catch (ValidationException $e) {
            $this->assertSame([
                'product_id' => [__('validation.product_reservation.no_identity_stock')],
            ], $e->errors());
        }

        $this->assertFalse($callbackCalled, 'The callback is called for a reached fund product limit.');

        foreach ([$productA, $productC] as $product) {
            $result = $voucher->transactionWithReservationLock(
                $product,
                fn (Voucher $lockedVoucher, Product $lockedProduct) => [$lockedVoucher->id, $lockedProduct->id],
            );

            $this->assertSame([$voucher->id, $product->id], $result);
        }
    }

    /**
     * Make 4 products (A,B,C,D)
     * Create fund product limit scope ALL_EXCEPT_SELECTED and one product (A) with limit 2
     * Reserve product A (as it excluded)
     * Assert product B available to reserve and reserve it
     * Assert product C available to reserve and reserve it
     * Assert product D is not available to reserve.
     * @return void
     */
    public function testFundProductLimitCaseScopeAllExceptSelected(): void
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

        $this->makeFundProductLimit($fund, FundProductLimit::SCOPE_ALL_EXCEPT_SELECTED, 2, [$productA->id]);

        $this->createReservation($voucher, $productA);
        $this->createReservation($voucher, $productB);
        $this->createReservation($voucher, $productC);
        $this->createReservation($voucher, $productD, false);
    }

    /**
     * Make 3 products (A,B,C)
     * Create fund product limit scope ALL_EXCEPT_SELECTED with limit 2
     * Reserve products A and B
     * Assert product A can still be reserved again because it is already counted
     * Assert product C is not available because it would add a third distinct product.
     * @return void
     */
    public function testFundProductLimitCaseScopeAllExceptSelectedAllowsRepeatReservation(): void
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

        $this->makeFundProductLimit($fund, FundProductLimit::SCOPE_ALL_EXCEPT_SELECTED, 2);

        $this->createReservation($voucher, $productA);
        $this->createReservation($voucher, $productB);
        $this->createReservation($voucher, $productA);
        $this->createReservation($voucher, $productC, false);
    }

    /**
     * Make 4 products (A,B,C,D)
     * Create fund product limit scope ONLY_SELECTED and products (A,B,D) with limit 2
     * Create fund product limit scope ONLY_SELECTED and products (A,B,C) with limit 1
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

        $this->makeFundProductLimit(
            $fund,
            FundProductLimit::SCOPE_ONLY_SELECTED,
            2,
            [$productA->id, $productB->id, $productD->id],
        );
        $this->makeFundProductLimit(
            $fund,
            FundProductLimit::SCOPE_ONLY_SELECTED,
            1,
            [$productA->id, $productB->id, $productC->id],
        );

        $this->createReservation($voucher, $productA);
        $this->createReservation($voucher, $productB, false);
        $this->createReservation($voucher, $productC, false);
        $this->createReservation($voucher, $productD);
    }

    /**
     * Make 4 products (A,B,C,D)
     * Create fund product limit scope ALL_EXCEPT_SELECTED and products (A,B) with limit 2
     * Create fund product limit scope ONLY_SELECTED and products (A,B,C) with limit 1
     * Reserve product A
     * Assert product B is not available to reserve (as it’s part of limit #2)
     * Assert product C is not available to reserve (as it’s part of limit #2)
     * Assert product D is available to reserve (as part of limits #1).
     * @return void
     */
    public function testFundProductLimitCaseTwoDifferentScopes(): void
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

        $this->makeFundProductLimit(
            $fund,
            FundProductLimit::SCOPE_ALL_EXCEPT_SELECTED,
            2,
            [$productA->id, $productB->id],
        );
        $this->makeFundProductLimit(
            $fund,
            FundProductLimit::SCOPE_ONLY_SELECTED,
            1,
            [$productA->id, $productB->id, $productC->id],
        );

        $this->createReservation($voucher, $productA);
        $this->createReservation($voucher, $productB, false);
        $this->createReservation($voucher, $productC, false);
        $this->createReservation($voucher, $productD);
    }

    /**
     * Make 3 products (A,B,C)
     * Create fund product limit scope ONLY_SELECTED and two products (A,B) with limit 1
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

        $this->makeFundProductLimit($fund, FundProductLimit::SCOPE_ONLY_SELECTED, 1, [$productA->id, $productB->id]);

        $reservationA = $this->createReservation($voucher, $productA);
        $this->createReservation($voucher, $productB, false);

        $reservationA->rejectOrCancelProvider($organization->employees()->first());
        $this->createReservation($voucher, $productB);
        $this->createReservation($voucher, $productC);
    }

    /**
     * Make 3 products (A,B,C)
     * Create fund product limit scope ONLY_SELECTED and two products (A,B) with limit 1
     * Create fund product limit scope ONLY_SELECTED and two products (A,C) with limit 1
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

        $limit1 = $this->makeFundProductLimit(
            $fund,
            FundProductLimit::SCOPE_ONLY_SELECTED,
            1,
            [$productA->id, $productB->id],
        );
        $this->makeFundProductLimit($fund, FundProductLimit::SCOPE_ONLY_SELECTED, 1, [$productA->id, $productC->id]);

        $this->createReservation($voucher, $productA);
        $this->createReservation($voucher, $productB, false);
        $this->createReservation($voucher, $productC, false);

        $limit1->update(['state' => FundProductLimit::STATE_INACTIVE]);

        $this->createReservation($voucher, $productB);
    }

    /**
     * Create fund product limit by amount (budget) with limit 50.50
     * Assert limit_by and formatted limit are returned
     * Update the limit without limit_by and assert it stays limited by amount
     * Assert decimal limit is not accepted when limited by products.
     * @return void
     */
    public function testStoreAndUpdateFundProductLimitByAmount(): void
    {
        $organization = $this->makeTestOrganization($this->makeIdentity(), [
            'allow_fund_product_limits' => true,
        ]);

        $fund = $this->makeTestFund($organization);

        $provider = $this->makeTestProviderOrganization($this->makeIdentity());
        $product = $this->createProductForReservation($provider, [$fund]);

        $data = [
            'fund_id' => $fund->id,
            'state' => FundProductLimit::STATE_ACTIVE,
            'type' => FundProductLimit::SCOPE_ONLY_SELECTED,
            'products' => [$product->id],
        ];

        $response = $this->apiMakeFundProductLimitRequest($organization, [
            ...$data,
            'limit_by' => FundProductLimit::LIMIT_BY_AMOUNT,
            'limit' => 50.5,
        ], $organization->identity)
            ->assertSuccessful()
            ->assertJsonPath('data.limit_by', FundProductLimit::LIMIT_BY_AMOUNT)
            ->assertJsonPath('data.limit', '50.50')
            ->assertJsonPath('data.limit_locale', '€ 50,50');

        $fundProductLimit = FundProductLimit::find($response->json('data.id'));

        $this->apiUpdateFundProductLimitRequest($organization, $fundProductLimit, [
            ...$data,
            'limit' => 70.25,
        ], $organization->identity)
            ->assertSuccessful()
            ->assertJsonPath('data.limit_by', FundProductLimit::LIMIT_BY_AMOUNT)
            ->assertJsonPath('data.limit', '70.25');

        $this->apiUpdateFundProductLimitRequest($organization, $fundProductLimit, [
            ...$data,
            'limit_by' => FundProductLimit::LIMIT_BY_PRODUCTS,
            'limit' => 1.5,
        ], $organization->identity)->assertJsonValidationErrorFor('limit');

        $this->apiUpdateFundProductLimitRequest($organization, $fundProductLimit, [
            ...$data,
            'limit_by' => FundProductLimit::LIMIT_BY_PRODUCTS,
            'limit' => 2,
        ], $organization->identity)
            ->assertSuccessful()
            ->assertJsonPath('data.limit_by', FundProductLimit::LIMIT_BY_PRODUCTS)
            ->assertJsonPath('data.limit', 2);
    }

    /**
     * Make products A (20), B (30), C (20) and D (40) out of the limit
     * Create fund product limit by amount scope ONLY_SELECTED and products (A,B,C) with limit 50
     * Reserve product A two times (40)
     * Assert products A, B and C are not available to reserve (exceeds 50)
     * Assert product D is available as it not in limit.
     * @return void
     */
    public function testFundProductLimitByAmountSameProductTwice(): void
    {
        [$voucher, $productA, $productB, $productC, $productD] = $this->makeFundProductLimitByAmountCase();

        $this->createReservation($voucher, $productA);
        $this->createReservation($voucher, $productA);
        $this->createReservation($voucher, $productA, false);
        $this->createReservation($voucher, $productB, false);
        $this->createReservation($voucher, $productC, false);
        $this->createReservation($voucher, $productD);
    }

    /**
     * Make products A (20), B (30), C (20) and D (40) out of the limit
     * Create fund product limit by amount scope ONLY_SELECTED and products (A,B,C) with limit 50
     * Reserve product A and product B (50)
     * Assert products C and A are not available to reserve (exceeds 50).
     * @return void
     */
    public function testFundProductLimitByAmountBudgetReached(): void
    {
        [$voucher, $productA, $productB, $productC] = $this->makeFundProductLimitByAmountCase();

        $this->createReservation($voucher, $productA);
        $this->createReservation($voucher, $productB);
        $this->createReservation($voucher, $productC, false);
        $this->createReservation($voucher, $productA, false);
    }

    /**
     * Make products A (20), B (30), C (20) and D (40) out of the limit
     * Create fund product limit by amount scope ONLY_SELECTED and products (A,B,C) with limit 50
     * Reserve product B, assert it is not available to reserve again (exceeds 50)
     * Cancel reservation for product B
     * Assert product B is available to reserve again.
     * @throws Throwable
     * @return void
     */
    public function testFundProductLimitByAmountCanceledReservation(): void
    {
        [$voucher, , $productB] = $this->makeFundProductLimitByAmountCase();

        $reservation = $this->createReservation($voucher, $productB);
        $this->createReservation($voucher, $productB, false);

        $reservation->cancelByClient();
        $this->createReservation($voucher, $productB);
    }

    /**
     * Make products A (20), B (30), C (20) and D (40)
     * Create fund product limit by amount scope ALL_EXCEPT_SELECTED and product (A) with limit 60
     * Reserve products B and C (50)
     * Assert product D is not available to reserve (exceeds 60)
     * Assert product A is available as it excluded from the limit.
     * @return void
     */
    public function testFundProductLimitByAmountScopeAllExceptSelected(): void
    {
        [$voucher, $productA, $productB, $productC, $productD] = $this->makeFundProductLimitByAmountCase(
            FundProductLimit::SCOPE_ALL_EXCEPT_SELECTED,
            60,
        );

        $this->createReservation($voucher, $productB);
        $this->createReservation($voucher, $productC);
        $this->createReservation($voucher, $productD, false);
        $this->createReservation($voucher, $productA);
    }

    /**
     * Make 2 vouchers for the same identity in one fund and 2 products (A,B)
     * Create fund product limit scope ONLY_SELECTED and products (A,B) with limit 1
     * Reserve product A with voucher #1
     * Assert product B is not available to reserve with voucher #1
     * Assert product B is available to reserve with voucher #2 (limits are applied per voucher)
     * Assert product A is not available to reserve with voucher #2.
     * @return void
     */
    public function testFundProductLimitCaseTwoVouchers(): void
    {
        $organization = $this->makeTestOrganization($this->makeIdentity());
        $fund = $this->makeTestFund($organization);

        $voucher1 = $fund->makeVoucher($organization->identity, [
            'state' => Voucher::STATE_ACTIVE,
        ], amount: 10000);

        $voucher2 = $fund->makeVoucher($organization->identity, [
            'state' => Voucher::STATE_ACTIVE,
        ], amount: 10000);

        $provider = $this->makeTestProviderOrganization($this->makeIdentity());
        $productA = $this->createProductForReservation($provider, [$fund]);
        $productB = $this->createProductForReservation($provider, [$fund]);

        $this->makeFundProductLimit($fund, FundProductLimit::SCOPE_ONLY_SELECTED, 1, [$productA->id, $productB->id]);

        $this->createReservation($voucher1, $productA);
        $this->createReservation($voucher1, $productB, false);
        $this->createReservation($voucher2, $productB);
        $this->createReservation($voucher2, $productA, false);
    }

    /**
     * Make voucher #1 for identity and 3 products (A,B,C)
     * Create fund product limit scope ONLY_SELECTED and products (A,B,C) with limit 1
     * Reserve product A with voucher #1
     * Assert product C has no limit available for the fund on the product details page
     * Make voucher #2 for the same identity in the same fund
     * Assert product C has limit available for the fund (voucher #2 can still be used)
     * Reserve product B with voucher #2
     * Assert product C has no limit available for the fund (both vouchers reached the limit).
     * @return void
     */
    public function testFundProductLimitCaseTwoVouchersProductFunds(): void
    {
        $organization = $this->makeTestOrganization($this->makeIdentity());
        $fund = $this->makeTestFund($organization);

        $voucher1 = $fund->makeVoucher($organization->identity, [
            'state' => Voucher::STATE_ACTIVE,
        ], amount: 10000);

        $provider = $this->makeTestProviderOrganization($this->makeIdentity());
        $productA = $this->createProductForReservation($provider, [$fund]);
        $productB = $this->createProductForReservation($provider, [$fund]);
        $productC = $this->createProductForReservation($provider, [$fund]);

        $this->makeFundProductLimit($fund, FundProductLimit::SCOPE_ONLY_SELECTED, 1, [
            $productA->id, $productB->id, $productC->id,
        ]);

        $this->createReservation($voucher1, $productA);
        $this->assertEquals(0, $this->getProductFundLimitAvailable($voucher1, $productC));

        $voucher2 = $fund->makeVoucher($organization->identity, [
            'state' => Voucher::STATE_ACTIVE,
        ], amount: 10000);

        $this->assertGreaterThan(0, $this->getProductFundLimitAvailable($voucher1, $productC));

        $this->createReservation($voucher2, $productB);
        $this->assertEquals(0, $this->getProductFundLimitAvailable($voucher1, $productC));
    }

    /**
     * Make 2 vouchers for the same identity in one fund and 2 products (1,2)
     * Set both voucher multipliers to 1 and product #1 allowance to 1 per voucher
     * Create fund product limit scope ONLY_SELECTED and products (1,2) with limit 1
     * Reserve product #1 with voucher #1
     * Reserve product #2 with voucher #2
     * Assert product #1 is not available with voucher #1 (product allowance reached)
     * Assert product #1 is not available with voucher #2 (fund product limit reached)
     * Assert product #1 has no limit available for the fund on the product details page.
     * @return void
     */
    public function testFundProductLimitAvailabilityRequiresVoucherWithRemainingProductAllowance(): void
    {
        $organization = $this->makeTestOrganization($this->makeIdentity());
        $fund = $this->makeTestFund($organization);

        $voucher1 = $fund->makeVoucher($organization->identity, [
            'state' => Voucher::STATE_ACTIVE,
        ], amount: 10000, limitMultiplier: 1);

        $voucher2 = $fund->makeVoucher($organization->identity, [
            'state' => Voucher::STATE_ACTIVE,
        ], amount: 10000, limitMultiplier: 1);

        $provider = $this->makeTestProviderOrganization($this->makeIdentity());
        $product1 = $this->createProductForReservation($provider, [$fund]);
        $product2 = $this->createProductForReservation($provider, [$fund]);

        $product1->getFundProviderProduct($fund)->update([
            'limit_per_identity' => 1,
            'limit_per_identity_unlimited' => false,
        ]);

        $this->makeFundProductLimit($fund, FundProductLimit::SCOPE_ONLY_SELECTED, 1, [$product1->id, $product2->id]);

        $this->createReservation($voucher1, $product1);
        $this->createReservation($voucher2, $product2);
        $this->createReservation($voucher1, $product1, false);
        $this->createReservation($voucher2, $product1, false);

        $this->assertSame(0, $this->getProductFundLimitAvailable($voucher1, $product1));
    }

    /**
     * @param Voucher $voucher
     * @param Product $product
     * @param bool $assertCreated
     * @return ProductReservation|null
     */
    public function createReservation(
        Voucher $voucher,
        Product $product,
        bool $assertCreated = true,
    ): ?ProductReservation {
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

    /**
     * @param Voucher $voucher
     * @param Product $product
     * @return int|null
     */
    protected function getProductFundLimitAvailable(Voucher $voucher, Product $product): ?int
    {
        $response = $this->apiGetProductRequest($product, $voucher->identity)->assertSuccessful();

        return collect($response->json('data.funds'))->firstWhere('id', $voucher->fund_id)['limit_available'] ?? null;
    }

    /**
     * Make products A (20), B (30), C (20) and D (40)
     * Create fund product limit by amount with given scope and limit:
     * ONLY_SELECTED - products (A,B,C) are limited, ALL_EXCEPT_SELECTED - product (A) is excluded.
     * @param string $type
     * @param int $limit
     * @return array
     */
    protected function makeFundProductLimitByAmountCase(
        string $type = FundProductLimit::SCOPE_ONLY_SELECTED,
        int $limit = 50,
    ): array {
        $organization = $this->makeTestOrganization($this->makeIdentity());
        $fund = $this->makeTestFund($organization);

        // limit multiplier allows to reserve the same product more than once
        $voucher = $fund->makeVoucher($fund->organization->identity, [
            'state' => Voucher::STATE_ACTIVE,
        ], amount: 10000, limitMultiplier: 3);

        $provider = $this->makeTestProviderOrganization($this->makeIdentity());
        $productA = $this->createProductForReservation($provider, [$fund], 20);
        $productB = $this->createProductForReservation($provider, [$fund], 30);
        $productC = $this->createProductForReservation($provider, [$fund], 20);
        $productD = $this->createProductForReservation($provider, [$fund], 40);

        $this->makeFundProductLimit(
            $fund,
            $type,
            $limit,
            $type === FundProductLimit::SCOPE_ONLY_SELECTED
                ? [$productA->id, $productB->id, $productC->id]
                : [$productA->id],
            FundProductLimit::LIMIT_BY_AMOUNT,
        );

        return [$voucher, $productA, $productB, $productC, $productD];
    }
}
