<?php

namespace Tests\Traits;

use App\Events\FundProviders\FundProviderApprovedBudget;
use App\Events\FundProviders\FundProviderApprovedProducts;
use App\Events\Funds\FundProviderApplied;
use App\Mail\Vouchers\VoucherAssignedBudgetMail;
use App\Mail\Vouchers\VoucherAssignedProductMail;
use App\Mail\Vouchers\VoucherAssignedSubsidyMail;
use App\Models\Fund;
use App\Models\FundProvider;
use App\Models\Identity;
use App\Models\Product;
use App\Models\Voucher;
use App\Models\VoucherTransaction;
use App\Scopes\Builders\FundQuery;
use App\Services\Forus\TestData\TestData;
use App\Services\MailDatabaseLoggerService\Traits\AssertsSentEmails;
use App\Traits\DoesTesting;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Str;

trait VoucherTestTrait
{
    use WithFaker, AssertsSentEmails, DoesTesting, MakesTestIdentities;

    /**
     * @var string
     */
    protected string $apiOrganizationUrl = '/api/v1/platform/organizations/%s';

    /**
     * @var string
     */
    protected string $apiFundUrl = '/api/v1/platform/funds/%s';

    /**
     * @var Product[]
     */
    protected array $products = [];

    /**
     * @var Identity[]
     */
    protected array $identities = [];

    /**
     * @var Product[]
     */
    protected array $notAssignedProducts = [];

    /**
     * @var Product[]
     */
    protected array $emptyStockProducts = [];

    /**
     * @return void
     */
    protected function resetProperties(): void
    {
        $this->products = [];
        $this->notAssignedProducts = [];
        $this->identities = [];
    }

    /**
     * @param Fund $fund
     * @return void
     */
    protected function makeFundFormulaProducts(Fund $fund): void
    {
        if (!$fund->fund_formula_products->count()) {
            array_map(function ($value) use ($fund) {
                /** @var Product $product */
                $product = array_random($this->products);
                $fund->fund_formula_products()->updateOrCreate([
                    'product_id' => $product->id,
                ], [
                    'price' => $product->price,
                    'record_type_key_multiplier' => null,
                ]);
            }, range(0, 3));

            $fund->load('fund_formula_products');
        }
    }

    /**
     * @param Fund $fund
     * @param string $type
     * @param array $assert
     * @param int $index
     * @param array $vouchers
     * @return array
     * @throws \Throwable
     */
    protected function getVoucherFields(
        Fund $fund,
        string $type,
        array $assert,
        int $index,
        array $vouchers
    ): array {
        $params = [];
        $makeTransaction = $assert['with_transaction'] ?? false;
        $amount = null;
        $productId = null;
        $sameAssignBy = $assert['same_assign_by'] ?? 0;
        $activationCode = $assert['activation_code'] ?? 0;

        if ($sameAssignBy > $index && count($vouchers)) {
            $params[$assert['assign_by']] = $vouchers[$index - 1][$assert['assign_by']];
        } else {
            $params = $this->assignByFields($assert, $index);
        }

        if ($type === 'budget') {
            $amount = ($assert['amount_over_limit'] ?? false)
                ? $fund->getMaxAmountPerVoucher() + 10
                : rand(1, $fund->getMaxAmountPerVoucher());

        } elseif ($type === 'product') {
            $productId = match ($assert['product'] ?? 'assigned') {
                'assigned' => array_random($this->products)->id,
                'not_assigned' => array_random($this->notAssignedProducts)->id,
                'empty_stock' => array_random($this->emptyStockProducts)->id,
                default => null,
            };
        }

        return array_merge($params, [
            'activate' => $assert['activate'] ?? true,
            'activation_code' => $activationCode > $index,
            'limit_multiplier' => $type === 'budget' ? rand(1, 3) : 1,
            'expire_at' => now()->addDays(30)->format('Y-m-d'),
            'note' => $this->faker()->sentence(),
        ], $this->recordsFields(),
            $amount ? ['amount' => $amount] : [],
            $productId ? ['product_id' => $productId] : [],
            $makeTransaction ? $this->getTransactionFields() : [],
            $assert['replacement'] ?? [],
        );
    }

    /**
     * @param array $assert
     * @param int $index
     * @return array
     * @throws \Throwable
     */
    protected function assignByFields(array $assert, int $index): array
    {
        $params = [];
        if ($assert['existing_identity'] ?? false) {
            $identity = null;
            if ($assert['assign_by'] === 'bsn') {
                $identity = $this->makeIdentity(null, ['bsn' => (string) $this->randomFakeBsn()]);
                $params['bsn'] = $identity->record_bsn->value;
            } elseif ($assert['assign_by'] === 'email') {
                $identity = $this->makeIdentity($this->makeUniqueEmail());
                $params['email'] = $identity->primary_email->email;
            } else {
                $params['client_uid'] = Str::random();
            }

            $identity && $this->identities[$index] = $identity;
        } else {
            $params[$assert['assign_by']] = match ($assert['assign_by']) {
                'bsn' => (string) $this->randomFakeBsn(),
                'email' => $this->makeUniqueEmail(),
                'client_uid' => Str::random()
            };
        }

        return $params;
    }

    /**
     * @return array
     */
    protected function recordsFields(): array
    {
        return [
            'records' => [
                'given_name' => $this->faker()->firstName,
                'family_name' => $this->faker()->lastName,
                'birth_date' => Carbon::create(2000, 1, 5)->format('Y-m-d'),
                'address' => $this->faker()->address,
            ],
        ];
    }

    /**
     * @return array
     */
    protected function getTransactionFields(): array
    {
        return [
            'direct_payment_iban' => $this->faker()->iban('NL'),
            'direct_payment_name' => $this->faker()->firstName . ' ' . $this->faker()->lastName,
        ];
    }

    /**
     * @param Builder $query
     * @param Carbon $startDate
     * @param array $vouchers
     * @param array $assert
     * @return void
     */
    protected function checkVouchers(
        Builder $query,
        Carbon $startDate,
        array $vouchers,
        array $assert
    ): void {
        $sortedVouchers = [];
        $sameAssignBy = $assert['same_assign_by'] ?? 0;
        $assertExistingIdentity = $assert['existing_identity'] ?? false;
        $createdVouchers = $query->get();
        $this->assertEquals(count($vouchers), $createdVouchers->count());

        foreach ($vouchers as $index => $voucherArr) {
            /** @var Voucher $voucher */
            $voucher = $createdVouchers->first(fn(Voucher $item) => $item->note === $voucherArr['note']);
            $this->assertNotNull($voucher);
            $sortedVouchers[] = $voucher;

            // check activation and transaction
            $this->checkActivation($voucher, $voucherArr['activate']);
            $this->checkTransactions(
                $voucher, $voucherArr, $assert['with_transaction'] ?? false
            );

            // check identity assign
            if ($assertExistingIdentity) {
                $identity = $this->identities[$index] ?? null;
                $this->assertNotNull($identity);

                $this->checkExistingIdentityAssign($identity, $voucher, $startDate, $assert['assign_by']);
            } else {
                match ($assert['assign_by']) {
                    'bsn' => $this->checkAssignedToNewBsn($voucher, $voucherArr),
                    'email' => $this->checkAssignedToNewEmail($voucher, $startDate),
                    'client_uid' => $this->checkAssignedToClientUid($voucher, $voucherArr),
                };
            }

            $this->checkFieldsEquals($voucher, $voucherArr);
            $this->checkActivationCode($voucher, $voucherArr);

            if ($assert['assign_by'] === 'email') {
                $this->checkFundFormulaProducts($voucher, $startDate);
            }
        }

        if ($sameAssignBy > 0) {
            $this->checkSameAssignBy($sortedVouchers, $assert);
        }

        $this->checkAsSponsor($query, $startDate, $vouchers, $assert);
        $this->checkAsIdentity($query, $startDate, $vouchers, $assert);
    }

    /**
     * @param Voucher[]|array $vouchers
     * @param $assert
     * @return void
     */
    protected function checkSameAssignBy(array $vouchers, $assert): void
    {
        $sameAssignBy = $assert['same_assign_by'];
        $activationCode = $assert['activation_code'] ?? 0;
        $type = $assert['assign_by'];

        $baseAssignBy = $this->getVoucherAssignedByValue($vouchers[0], $type);
        $baseActivationCode = $vouchers[0]->activation_code;
        foreach ($vouchers as $index => $voucher) {
            $assignBy = $this->getVoucherAssignedByValue($voucher, $type);

            $sameAssignBy > $index
                ? $this->assertEquals($baseAssignBy, $assignBy)
                : $this->assertNotEquals($baseAssignBy, $assignBy);

            if ($type === 'client_uid' && $sameAssignBy > $index) {
                $activationCode > $index
                    ? $this->assertEquals($baseActivationCode, $voucher->activation_code)
                    : $this->assertNotEquals($baseActivationCode, $voucher->activation_code);
            }
        }
    }

    /**
     * @param Voucher $voucher
     * @param array $voucherArr
     * @return void
     */
    protected function checkActivationCode(Voucher $voucher, array $voucherArr): void
    {
        $voucherArr['activation_code']
            ? $this->assertNotNull($voucher->activation_code)
            : $this->assertNull($voucher->activation_code);
    }

    /**
     * @param Voucher $voucher
     * @param Carbon $startDate
     * @return void
     */
    protected function checkFundFormulaProducts(Voucher $voucher, Carbon $startDate): void
    {
        if ($voucher->isBudgetType() && $voucher->identity) {
            foreach ($voucher->fund->fund_formula_products as $formulaProduct) {
                $multiplier = $formulaProduct->getIdentityMultiplier($voucher->identity_address);

                $productVoucherCount = Voucher::query()
                    ->where('identity_address', $voucher->identity_address)
                    ->where('note', $voucher->note)
                    ->where('product_id', $formulaProduct->product_id)
                    ->where('created_at', '>=', $startDate)
                    ->where('amount', $formulaProduct->price)
                    ->count();

                $this->assertEquals($multiplier, $productVoucherCount);
            }
        }
    }

    /**
     * @param Voucher $voucher
     * @param string $type
     * @return string|null
     */
    protected function getVoucherAssignedByValue(Voucher $voucher, string $type): ?string
    {
        return match($type) {
            'client_uid' => $voucher->client_uid,
            'bsn' => $voucher->identity?->bsn ?? ($voucher->voucher_relation->bsn ?? null),
            'email' => $voucher->identity?->email,
        };
    }

    /**
     * @param Identity $identity
     * @param Voucher $voucher
     * @param Carbon $startDate
     * @param string $type
     * @return void
     */
    protected function checkExistingIdentityAssign(
        Identity $identity,
        Voucher $voucher,
        Carbon $startDate,
        string $type
    ): void {
        $this->assertEquals($identity->address, $voucher->identity->address);

        if ($type === 'email' && $voucher->isBudgetType()) {
            $mailClass = $this->getMailableClass($voucher);
            $this->assertMailableSent($voucher->identity->email, $mailClass, $startDate);
        }
    }

    /**
     * @param Voucher $voucher
     * @param array $voucherArr
     * @return void
     */
    protected function checkAssignedToNewBsn(Voucher $voucher, array $voucherArr): void
    {
        $this->assertNotNull($voucher->voucher_relation);
        $this->assertEquals($voucherArr['bsn'], $voucher->voucher_relation->bsn);

        \Cache::flush();
        $identity = $this->makeIdentity(null, ['bsn' => $voucher->voucher_relation->bsn]);

        $headers = $this->makeApiHeaders($this->makeIdentityProxy($identity));
        $response = $this->post(sprintf($this->apiFundUrl . '/check', $voucher->fund_id), [], $headers);
        $response->assertSuccessful();
        $this->assertNotEmpty($response['vouchers']);

        $voucher->refresh();

        $this->assertNotNull($voucher->identity);
        $this->assertEquals($identity->address, $voucher->identity->address);

        $this->checkActivation($voucher, true);
    }

    /**
     * @param Voucher $voucher
     * @param Carbon $startDate
     * @return void
     */
    protected function checkAssignedToNewEmail(Voucher $voucher, Carbon $startDate): void
    {
        $this->assertNotNull($voucher->identity);

        $identity = Identity::query()
            ->where('created_at', '>=', $startDate)
            ->whereRelation('primary_email', 'email', $voucher->identity->email)
            ->first();

        $this->assertNotNull($identity);
        $this->assertEquals($identity->address, $voucher->identity->address);

        if ($voucher->isBudgetType()) {
            $mailClass = $this->getMailableClass($voucher);
            $this->assertMailableSent($voucher->identity->email, $mailClass, $startDate);
        }
    }

    /**
     * @param Voucher $voucher
     * @param array $voucherArr
     * @return void
     */
    protected function checkAssignedToClientUid(Voucher $voucher, array $voucherArr): void
    {
        $this->assertEquals($voucherArr['client_uid'], $voucher->client_uid);
    }

    /**
     * @param Voucher $voucher
     * @return string
     */
    protected function getMailableClass(Voucher $voucher): string
    {
        $voucherType = $voucher->isBudgetType()
            ? ($voucher->fund->isTypeBudget() ? 'budget' : 'subsidy')
            : 'product';

        return match ($voucherType) {
            'budget' => VoucherAssignedBudgetMail::class,
            'subsidy' => VoucherAssignedSubsidyMail::class,
            'product' => VoucherAssignedProductMail::class
        };
    }

    /**
     * @param Voucher $voucher
     * @param bool $assertActive
     * @return void
     */
    protected function checkActivation(
        Voucher $voucher,
        bool $assertActive
    ): void {
        $this->assertEquals(
            $voucher->state, $assertActive ? Voucher::STATE_ACTIVE : Voucher::STATE_PENDING
        );
    }

    /**
     * @param Voucher $voucher
     * @param array $voucherArr
     * @param bool $assertTransaction
     * @return void
     */
    protected function checkTransactions(
        Voucher $voucher,
        array $voucherArr,
        bool $assertTransaction
    ): void {
        if ($assertTransaction) {
            $transaction = $voucher->transactions()
                ->where('created_at', '>=', $voucher->created_at)
                ->where('target', VoucherTransaction::TARGET_IBAN)
                ->where('target_iban', $voucherArr['direct_payment_iban'])
                ->where('target_name', $voucherArr['direct_payment_name'])
                ->first();

            $this->assertNotNull($transaction);
        } else {
            $transaction = $voucher->transactions()
                ->where('created_at', '>=', $voucher->created_at)
                ->where('target', VoucherTransaction::TARGET_IBAN)
                ->first();

            $this->assertNull($transaction);
        }
    }

    /**
     * @param Voucher $voucher
     * @param array $voucherArr
     * @return void
     */
    protected function checkFieldsEquals(
        Voucher $voucher,
        array $voucherArr
    ): void {
        $this->assertEquals($voucher->limit_multiplier, $voucherArr['limit_multiplier']);
        $this->assertEquals($voucher->expire_at?->format('Y-m-d'), $voucherArr['expire_at']);
    }

    /**
     * @param Builder $query
     * @param Carbon $startDate
     * @param array $vouchers
     * @param array $assert
     * @return void
     */
    protected function checkAsSponsor(
        Builder $query,
        Carbon $startDate,
        array $vouchers,
        array $assert
    ): void {}

    /**
     * @param Builder $query
     * @param Carbon $startDate
     * @param array $vouchers
     * @param array $assert
     * @return void
     */
    protected function checkAsIdentity(
        Builder $query,
        Carbon $startDate,
        array $vouchers,
        array $assert
    ): void {}

    /**
     * @param Fund $fund
     * @param Carbon $startDate
     * @param string $type
     * @return Builder
     */
    protected function getVouchersBuilder(Fund $fund, Carbon $startDate, string $type): Builder
    {
        $createdVouchers = Voucher::query()
            ->where('fund_id', $fund->id)
            ->where('created_at', '>=', $startDate);

        $type === 'budget'
            ? $createdVouchers->whereNull('product_id')
            : $createdVouchers->whereNotNull('product_id');

        return $createdVouchers;
    }

    /**
     * @param Fund $fund
     * @param array $testCase
     * @return void
     * @throws \Throwable
     */
    protected function makeProviderAndProducts(Fund $fund, array $testCase): void
    {
        // make products and assign to fund
        $providerIdentity = $this->makeIdentity($this->makeUniqueEmail('provider_'));
        $this->products = $this->makeProducts($providerIdentity, $fund);

        // make products with stock 0 and assign to fund
        $providerIdentity = $this->makeIdentity($this->makeUniqueEmail('provider_'));
        $this->emptyStockProducts = $this->makeProducts(
            $providerIdentity, $fund, 0, 'all'
        );

        // make not assigned for fund products
        $providerIdentity = $this->makeIdentity($this->makeUniqueEmail('provider_'));
        $this->notAssignedProducts = $this->makeProducts($providerIdentity);

        if ($testCase['type'] === 'budget') {
            $this->makeFundFormulaProducts($fund);
        }
    }

    /**
     * @param Identity $identity
     * @param Fund|null $fund
     * @param int $stock
     * @param string $enableProducts
     * @return array
     * @throws \Throwable
     */
    protected function makeProducts(
        Identity $identity,
        ?Fund $fund = null,
        int $stock = 50,
        string $enableProducts = 'by_limits'
    ): array {
        $testData = new TestData();

        $organization = $testData->makeOrganizations("Provider", $identity->address)[0];
        $products = $testData->makeProducts($organization, 10, [
            'total_amount' => $stock,
            'unlimited_stock' => false,
            'sold_out' => $stock === 0
        ]);
        $this->assertNotEmpty($products, 'Products not created');

        if ($fund) {
            /** @var FundProvider $fundProvider */
            $fundProvider = $fund->providers()->firstOrCreate([
                'organization_id' => $organization->id,
                'allow_budget' => $fund->isTypeBudget(),
                'allow_products' => $enableProducts === 'all',
                'state' => FundProvider::STATE_ACCEPTED,
            ]);

            FundProviderApplied::dispatch($fund, $fundProvider);

            if ($fundProvider->allow_budget) {
                FundProviderApprovedBudget::dispatch($fundProvider);
            }

            if ($fundProvider->allow_products) {
                FundProviderApprovedProducts::dispatch($fundProvider);
            }

            if ($enableProducts === 'by_limits') {
                $productsParams['enable_products'] = [];

                /** @var Product $product */
                foreach ($products as $product) {
                    $productsParams['enable_products'][] = array_merge([
                        'id' => $product->id,
                        'limit_per_identity' => 1,
                        'limit_total' => rand(1, $stock),
                    ], $fund->isTypeSubsidy() ? ['amount' => $product->price] : []);
                }

                $this->updateProducts($fund, $fundProvider, $productsParams);
            }
        }

        return $products;
    }

    /**
     * @param Fund $fund
     * @param FundProvider $fundProvider
     * @param array $params
     * @return void
     */
    protected function updateProducts(
        Fund $fund,
        FundProvider $fundProvider,
        array $params,
    ): void {
        $proxy = $this->makeIdentityProxy($fund->organization->identity);
        $headers = $this->makeApiHeaders($proxy);
        $url = sprintf(
            $this->apiOrganizationUrl . '/funds/%s/providers/%s',
            $fund->organization->id,
            $fund->id,
            $fundProvider->id
        );

        $response = $this->patch($url, $params, $headers);
        $response->assertSuccessful();
    }

    /**
     * @param $fundId
     * @return Fund
     */
    protected function findFund($fundId): Fund
    {
        /** @var Fund $fund */
        $fund = FundQuery::whereActiveFilter(Fund::where('id', $fundId))->first();
        $this->assertNotNull($fund, 'Fund not found');

        return $fund;
    }

    /**
     * @return int
     * @throws \Throwable
     */
    protected function randomFakeBsn(): int
    {
        static $randomBsn = [];

        do {
            try {
                $bsn = random_int(100000000, 900000000);
            } catch (\Throwable) {
                $bsn = false;
            }
        } while ($bsn && in_array($bsn, $randomBsn, true));

        return $randomBsn[] = $bsn;
    }
}
