<?php

namespace Tests\Feature;

use App\Mail\Vouchers\SendVoucherMail;
use App\Mail\Vouchers\ShareProductVoucherMail;
use App\Models\Fund;
use App\Models\FundConfig;
use App\Models\FundProvider;
use App\Models\Identity;
use App\Models\PhysicalCard;
use App\Models\Product;
use App\Models\Voucher;
use App\Models\VoucherTransaction;
use App\Scopes\Builders\FundProviderQuery;
use Carbon\Carbon;
use Exception;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Random\RandomException;
use Tests\TestCase;
use Tests\TestCases\VoucherTestCases;
use Tests\Traits\MakesProductReservations;
use Tests\Traits\MakesTestFunds;
use Tests\Traits\MakesTestOrganizations;
use Tests\Traits\TestsReservations;
use Tests\Traits\VoucherTestTrait;
use Throwable;

class VoucherTest extends TestCase
{
    use MakesTestFunds;
    use VoucherTestTrait;
    use TestsReservations;
    use DatabaseTransactions;
    use MakesTestOrganizations;
    use MakesProductReservations;

    /**
     * @var string
     */
    protected string $apiUrl = '/api/v1/platform/organizations/%s/sponsor/vouchers';

    /**
     * @var string
     */
    protected string $apiBaseUrl = '/api/v1/platform';

    /**
     * @throws Throwable
     * @return void
     */
    public function testVoucherCaseBudgetVouchers(): void
    {
        $this->processVoucherTestCase(VoucherTestCases::$featureTestCaseBudgetVouchers);
    }

    /**
     * @throws Throwable
     * @return void
     */
    public function testVoucherCaseProductVouchers(): void
    {
        $this->processVoucherTestCase(VoucherTestCases::$featureTestCaseProductVouchers);
    }

    /**
     * @throws Throwable
     * @return void
     */
    public function testVoucherCaseBudgetVouchersExceedAmount(): void
    {
        $this->processVoucherTestCase(VoucherTestCases::$featureTestCaseBudgetVouchersExceedAmount);
    }

    /**
     * @throws Throwable
     * @return void
     */
    public function testVoucherCaseBudgetVouchersNoBSNExceedAmount(): void
    {
        $this->processVoucherTestCase(VoucherTestCases::$featureTestCaseBudgetVouchersNoBSNExceedAmount);
    }

    /**
     * @throws Throwable
     * @return void
     */
    public function testVoucherCaseProductVouchersEdgeCases(): void
    {
        $this->processVoucherTestCase(VoucherTestCases::$featureTestCaseProductVouchersEdgeCases);
    }

    /**
     * @throws Throwable
     * @return void
     */
    public function testVoucherCaseSubsidyFundBudgetVouchers(): void
    {
        $this->processVoucherTestCase(VoucherTestCases::$featureTestCaseSubsidyFundBudgetVouchers);
    }

    /**
     * @throws Throwable
     * @return void
     */
    public function testVoucherCaseBudgetVouchersExcludedFields(): void
    {
        $this->processVoucherTestCase(VoucherTestCases::$featureTestCaseBudgetVouchersExcludedFields);
    }

    /**
     * @throws Throwable
     * @return void
     */
    public function testVoucherFundFormulaProductMultiplier(): void
    {
        $organization = $this->makeTestOrganization($this->makeIdentity());
        $identity = $this->makeIdentity(email: $this->makeUniqueEmail(), bsn: 123456789);

        $fund = $this->makeTestFund($organization, [], [
            'limit_generator_amount' => 100,
            'limit_voucher_total_amount' => 100,
            'generator_ignore_fund_budget' => true,
            'allow_prevalidations' => true,
        ]);

        $this->addTestCriteriaToFund($fund);

        $prevalidation = $this->makePrevalidationForTestCriteria($organization, $fund);
        $products = $this->makeProviderAndProducts($fund);

        $this->setFundFormulaProductsForFund($fund, array_random($products['approved'], 3), 'test_number');

        $prevalidation->assignToIdentity($identity);
        $this->makeVoucherForFundFormulaProduct($fund, $identity);
    }

    /**
     * @throws Exception
     * @return void
     */
    public function testDeactivateVoucherBySponsor(): void
    {
        $organization = $this->makeTestOrganization($this->makeIdentity());
        $fund = $this->makeTestFund($organization);

        $this->makeProviderAndProducts($fund, 1);

        $voucher = $this->makeTestVoucher($fund, identity: $this->makeIdentity());
        $product = $this->findProductForReservation($voucher);

        $reservation = $this->makeReservation($voucher, $product);
        $response = $this->makeReservationGetRequest($reservation);

        $response->assertSuccessful();

        $headers = $this->makeApiHeaders($voucher->fund->organization->identity);
        $url = $this->getSponsorApiUrl($voucher, '/deactivate');

        $response = $this->patch($url, ['note' => $this->faker->sentence()], $headers);
        $response->assertSuccessful();

        $this->assertTrue($voucher->refresh()->isDeactivated(), 'Voucher deactivation failed');

        $reservation->refresh();
        $this->assertTrue($reservation->isCanceledBySponsor(), 'Reservation cancel failed');
    }

    /**
     * @throws Exception
     * @return void
     */
    public function testDeactivateVoucherByRequester(): void
    {
        $organization = $this->makeTestOrganization($this->makeIdentity());
        $fund = $this->makeTestFund($organization, [], ['allow_blocking_vouchers' => true]);

        $this->makeProviderAndProducts($fund, 1);

        $voucher = $this->makeTestVoucher($fund, identity: $this->makeIdentity());
        $product = $this->findProductForReservation($voucher);

        $reservation = $this->makeReservation($voucher, $product);
        $response = $this->makeReservationGetRequest($reservation);

        $response->assertSuccessful();

        $headers = $this->makeApiHeaders($voucher->identity);
        $url = $this->getIdentityApiUrl($voucher, '/deactivate');

        $response = $this->post($url, ['note' => $this->faker->sentence()], $headers);
        $response->assertSuccessful();

        $this->assertTrue($voucher->refresh()->isDeactivated(), 'Voucher deactivation failed');

        $reservation->refresh();
        $this->assertTrue($reservation->isCanceledByClient(), 'Reservation cancel failed');
    }

    /**
     * @param Fund $fund
     * @param Identity $identity
     * @throws Throwable
     * @return void
     */
    protected function makeVoucherForFundFormulaProduct(Fund $fund, Identity $identity): void
    {
        $startDate = now();
        $headers = $this->makeApiHeaders($this->makeIdentityProxy($fund->organization->identity));

        $amount = random_int(1, $fund->getMaxAmountPerVoucher());

        $data = array_merge([
            'fund_id' => $fund->id,
            'assign_by_type' => 'email',
            'email' => $identity->primary_email->email,
            'activate' => true,
            'limit_multiplier' => 1,
            'expire_at' => now()->addDays(30)->format('Y-m-d'),
            'note' => $this->faker()->sentence(),
            'amount' => $amount,
        ], $this->recordsFields());

        $validateResponse = $this->postJson($this->getApiUrl($fund, '/validate'), $data, $headers);
        $uploadResponse = $this->postJson($this->getApiUrl($fund), $data, $headers);

        $validateResponse->assertSuccessful();
        $uploadResponse->assertSuccessful();

        /** @var Voucher $voucher */
        $voucher = $this->getVouchersBuilder($fund, $startDate, 'budget')->first();

        $this->assertNotNull($voucher);
        $this->assertFundFormulaProductVouchersCreatedByMainVoucher($voucher);
    }

    /**
     * @throws Throwable
     */
    protected function processVoucherTestCase(array $testCase): void
    {
        Cache::clear();

        $fund = $this->findFund($testCase['fund_id']);

        $fund->fund_config->forceFill($testCase['fund_config'] ?? [])->save();
        $fund->organization->forceFill($testCase['organization'] ?? [])->save();

        $this->addTestCriteriaToFund($fund);
        $products = $this->makeProviderAndProducts($fund);

        $this->setFundFormulaProductsForFund($fund, array_random($products['approved'], 3), 'test_number');

        foreach ($testCase['asserts'] as $assert) {
            $this->storeVoucher($fund, $assert, $products[$assert['product'] ?? 'approved']);
        }
    }

    /**
     * @param Fund $fund
     * @param array $assert
     * @param Product[] $products
     * @throws Throwable
     * @throws RandomException
     * @return void
     */
    protected function storeVoucher(Fund $fund, array $assert, array $products): void
    {
        $assert['vouchers_count'] = 1;
        $startDate = now();
        $headers = $this->makeApiHeaders($this->makeIdentityProxy($fund->organization->identity));

        $data = [
            'fund_id' => $fund->id,
            'assign_by_type' => $assert['assign_by'] === 'client_uid' ? 'activation_code' : $assert['assign_by'],
            ...$this->makeVoucherData($fund, $assert, $products)[0],
        ];

        $validateResponse = $this->postJson($this->getApiUrl($fund, '/validate'), $data, $headers);
        $uploadResponse = $this->postJson($this->getApiUrl($fund), $data, $headers);

        if ($assert['assert_created']) {
            $validateResponse->assertSuccessful();
            $uploadResponse->assertSuccessful();

            $vouchersBuilder = $this->getVouchersBuilder($fund, $startDate, $assert['type'] ?? 'budget');
            $this->assertVouchersCreated($vouchersBuilder, $startDate, [$data], $assert);
            $vouchersBuilder->each(fn (Voucher $voucher) => $this->deleteVoucher($voucher));
        } else {
            $validateResponse->assertJsonValidationErrors($assert['assert_errors'] ?? []);
            $uploadResponse->assertJsonValidationErrors($assert['assert_errors'] ?? []);
        }
    }

    /**
     * @param Builder $query
     * @param Carbon $startDate
     * @param array $vouchers
     * @param array $assert
     * @throws Exception
     * @throws Throwable
     * @return void
     */
    protected function checkAsSponsor(
        Builder $query,
        Carbon $startDate,
        array $vouchers,
        array $assert
    ): void {
        $createdVouchers = $query->get();
        $this->assertEquals(count($vouchers), $createdVouchers->count());

        foreach ($vouchers as $voucherArr) {
            /** @var Voucher $voucher */
            $voucher = $createdVouchers->first(fn (Voucher $item) => $item->note === $voucherArr['note']);
            $this->assertNotNull($voucher);

            if ($voucher->isPending()) {
                $this->assertAbilityActivateVoucher($voucher);
                $this->assertAbilityDeactivateVoucher($voucher);
                $this->assertAbilityReactivateVoucher($voucher);
            }

            $this->assertAbilityAssignVoucher($voucher, $assert);
            $this->assertAbilityUpdateLimitMultiplier($voucher);
            $this->assertAbilityGenerateActivationCode($voucher);

            if ($assert['type'] === 'budget') {
                Cache::clear();

                $this->assertAbilityAssignPhysicalCard($voucher);
                $this->assertAbilityRemovePhysicalCard($voucher);
                $this->assertAbilityCreatePhysicalCardRequest($voucher);
                $this->assertAbilityCreateTransactions($voucher);
                $this->assertAbilityCreateTopUp($voucher);
            }
        }
    }

    /**
     * @param Voucher $voucher
     * @param array $assert
     * @throws Throwable
     * @return void
     */
    protected function assertAbilityAssignVoucher(Voucher $voucher, array $assert): void
    {
        $voucher->refresh();

        if (!$voucher->identity) {
            $startDate = now();
            $params = [];
            $identity = null;
            $assign_by = $assert['sponsor_assign_by'] ?? 'bsn';
            $existingIdentity = $assert['sponsor_assign_existing_identity'] ?? true;

            if ($existingIdentity) {
                if ($assign_by === 'bsn') {
                    $identity = $this->makeIdentity(bsn: $this->randomFakeBsn());
                    $params['bsn'] = $identity->record_bsn->value;
                } elseif ($assign_by === 'email') {
                    $identity = $this->makeIdentity($this->makeUniqueEmail());
                    $params['email'] = $identity->primary_email->email;
                }
            } else {
                $params[$assign_by] = match ($assign_by) {
                    'bsn' => (string) $this->randomFakeBsn(),
                    'email' => $this->makeUniqueEmail(),
                };
            }

            $headers = $this->makeApiHeaders($voucher->fund->organization->identity);
            $url = $this->getSponsorApiUrl($voucher, '/assign');

            $response = $this->patch($url, $params, $headers);
            $response->assertSuccessful();

            $voucher->refresh();

            if ($existingIdentity) {
                $this->assertEquals($identity->address, $voucher->identity->address);

                if ($assign_by === 'email' && $voucher->isBudgetType()) {
                    $mailClass = $this->getMailableClass($voucher);
                    $this->assertMailableSent($voucher->identity->email, $mailClass, $startDate);
                }
            } else {
                match ($assign_by) {
                    'bsn' => $this->assertAssignedToNewBsn($voucher, $params),
                    'email' => $this->assertAssignedToNewEmail($voucher, $startDate),
                };
            }
        }
    }

    /**
     * @param Voucher $voucher
     * @return void
     */
    protected function assertAbilityActivateVoucher(Voucher $voucher): void
    {
        $voucher->refresh();

        $headers = $this->makeApiHeaders($voucher->fund->organization->identity);
        $url = $this->getSponsorApiUrl($voucher, '/activate');

        $response = $this->patch($url, ['note' => $this->faker->sentence()], $headers);
        $response->assertSuccessful();

        $this->assertTrue($voucher->refresh()->isActivated(), 'Voucher activation failed');
    }

    /**
     * @param Voucher $voucher
     * @return void
     */
    protected function assertAbilityDeactivateVoucher(Voucher $voucher): void
    {
        $voucher->refresh();

        $headers = $this->makeApiHeaders($voucher->fund->organization->identity);
        $url = $this->getSponsorApiUrl($voucher, '/deactivate');

        $response = $this->patch($url, ['note' => $this->faker->sentence()], $headers);
        $response->assertSuccessful();

        $this->assertTrue($voucher->refresh()->isDeactivated(), 'Voucher deactivation failed');
    }

    /**
     * @param Voucher $voucher
     * @return void
     */
    protected function assertAbilityReactivateVoucher(Voucher $voucher): void
    {
        $voucher->refresh();

        if ($voucher->isDeactivated()) {
            $headers = $this->makeApiHeaders($voucher->fund->organization->identity);
            $url = $this->getSponsorApiUrl($voucher, '/activate');

            $response = $this->patch($url, ['note' => $this->faker->sentence()], $headers);
            $response->assertSuccessful();

            $this->assertTrue($voucher->refresh()->isActivated(), 'Voucher reactivation failed');
        }
    }

    /**
     * @param Voucher $voucher
     * @throws Exception
     * @return void
     */
    protected function assertAbilityUpdateLimitMultiplier(Voucher $voucher): void
    {
        $voucher->refresh();

        $limitMultiplier = $voucher->limit_multiplier + random_int(1, 10);
        $headers = $this->makeApiHeaders($voucher->fund->organization->identity);
        $url = $this->getSponsorApiUrl($voucher);

        $response = $this->patch($url, ['limit_multiplier' => $limitMultiplier], $headers);
        $response->assertSuccessful();

        $this->assertEquals(
            $limitMultiplier,
            $voucher->refresh()->limit_multiplier,
            'Voucher update limit multiplier failed'
        );
    }

    /**
     * @param Voucher $voucher
     * @return void
     */
    protected function assertAbilityGenerateActivationCode(Voucher $voucher): void
    {
        $voucher->refresh();

        if (!$voucher->granted && !$voucher->activation_code && !$voucher->isDeactivated()) {
            $headers = $this->makeApiHeaders($voucher->fund->organization->identity);
            $url = $this->getSponsorApiUrl($voucher, '/activation-code');

            $response = $this->patch($url, [], $headers);
            $response->assertSuccessful();

            $this->assertNotEmpty(
                $voucher->refresh()->activation_code,
                'Voucher generate activation code failed'
            );
        }
    }

    /**
     * @param Voucher $voucher
     * @throws Exception
     * @return void
     */
    protected function assertAbilityCreateTransactions(Voucher $voucher): void
    {
        // make direct payment
        $voucher->fund->fund_config()->update(['allow_direct_payments' => false]);
        $this->makeDirectTransaction($voucher, false);

        $voucher->fund->fund_config()->update(['allow_direct_payments' => true]);
        $this->makeDirectTransaction($voucher);

        $voucher->fund->fund_config()->update(['vouchers_type' => FundConfig::VOUCHERS_TYPE_EXTERNAL]);
        $this->makeTransactionToProvider($voucher);

        $voucher->fund->fund_config()->update(['vouchers_type' => FundConfig::VOUCHERS_TYPE_INTERNAL]);
        $this->makeTransactionToProvider($voucher);
    }

    /**
     * @param Voucher $voucher
     * @param bool $assert
     * @throws Exception
     * @return void
     */
    protected function makeDirectTransaction(Voucher $voucher, bool $assert = true): void
    {
        $startDate = now();
        $amount = random_int(1, round($voucher->amount_available / 2));
        $organization = $voucher->fund->organization;

        $headers = $this->makeApiHeaders($organization->identity);
        $url = sprintf($this->apiOrganizationUrl . '/sponsor/transactions', $organization->id);

        $response = $this->post($url, [
            'voucher_id' => $voucher->id,
            'target' => VoucherTransaction::TARGET_IBAN,
            'target_iban' => $this->faker()->iban('NL'),
            'target_name' => $this->makeIbanName(),
            'amount' => $amount,
        ], $headers);

        if ($assert) {
            $response->assertSuccessful();

            $transaction = VoucherTransaction::query()
                ->where('voucher_id', $voucher->id)
                ->where('created_at', '>=', $startDate)
                ->where('amount', $amount)
                ->first();

            $this->assertNotNull($transaction, 'Voucher transaction did not created');
        } else {
            $response->assertJsonValidationErrors(['target']);
        }
    }

    /**
     * @param Voucher $voucher
     * @throws Exception
     * @return void
     */
    protected function makeTransactionToProvider(Voucher $voucher): void
    {
        $startDate = now();
        $amount = random_int(1, round($voucher->amount_available / 2));
        $organization = $voucher->fund->organization;
        $fundProvider = FundProviderQuery::whereApprovedForFundsFilter(
            FundProvider::query(),
            $voucher->fund_id,
            'allow_budget',
        )->first();

        $this->assertNotNull($fundProvider);
        $this->assertNotNull($fundProvider->organization);
        $provider = $fundProvider->organization;

        $headers = $this->makeApiHeaders($organization->identity);
        $url = sprintf($this->apiOrganizationUrl . '/sponsor/transactions', $organization->id);

        $response = $this->post($url, [
            'voucher_id' => $voucher->id,
            'target' => VoucherTransaction::TARGET_PROVIDER,
            'organization_id' => $provider->id,
            'amount' => $amount,
        ], $headers);

        $response->assertSuccessful();

        $transaction = VoucherTransaction::query()
            ->where('voucher_id', $voucher->id)
            ->where('created_at', '>=', $startDate)
            ->where('amount', $amount)
            ->first();

        $this->assertNotNull($transaction, 'Voucher transaction did not created');
    }

    /**
     * @param Voucher $voucher
     * @throws Exception
     * @return void
     */
    protected function assertAbilityCreateTopUp(Voucher $voucher): void
    {
        $voucher->fund->fund_config()->update(['allow_voucher_top_ups' => false]);
        $this->makeTopUp($voucher, false);

        $voucher->fund->fund_config()->update(['allow_voucher_top_ups' => true]);
        $this->makeTopUp($voucher);

        $voucher->fund->fund_config()->update(['vouchers_type' => FundConfig::VOUCHERS_TYPE_EXTERNAL]);
        $this->makeTopUp($voucher);

        $voucher->fund->fund_config()->update(['vouchers_type' => FundConfig::VOUCHERS_TYPE_INTERNAL]);
        $this->makeTopUp($voucher);
    }

    /**
     * @param Voucher $voucher
     * @param bool $assert
     * @throws Exception
     * @return void
     */
    protected function makeTopUp(Voucher $voucher, bool $assert = true): void
    {
        $startDate = now();
        $organization = $voucher->fund->organization;
        $maxAmount = min([
            $voucher->fund->fund_config->limit_voucher_top_up_amount,
            $voucher->fund->fund_config->limit_voucher_total_amount - $voucher->amount_total,
        ]);
        $amount = random_int(1, round($maxAmount / 2));

        $headers = $this->makeApiHeaders($organization->identity);
        $url = sprintf($this->apiOrganizationUrl . '/sponsor/transactions', $organization->id);
        $params = [
            'voucher_id' => $voucher->id,
            'target' => VoucherTransaction::TARGET_TOP_UP,
            'amount' => $amount,
        ];

        // test wrong amount
        $response = $this->post($url, array_merge($params, [
            'amount' => $amount + $maxAmount,
        ]), $headers);

        $response->assertJsonValidationErrors(array_merge(['amount'], $assert ? [] : ['target']));

        $response = $this->post($url, $params, $headers);

        if ($assert) {
            $response->assertSuccessful();

            $transaction = VoucherTransaction::query()
                ->where('voucher_id', $voucher->id)
                ->where('created_at', '>=', $startDate)
                ->where('amount', $amount)
                ->first();

            $this->assertNotNull($transaction, 'Voucher top up did not created');
        } else {
            $response->assertJsonValidationErrors(['target']);
        }
    }

    /**
     * @param Voucher $voucher
     * @throws Exception
     * @return void
     */
    protected function assertAbilityAssignPhysicalCard(Voucher $voucher): void
    {
        $voucher->fund->fund_config()->update(['allow_physical_cards' => false]);
        $this->assignPhysicalCard($voucher, 'sponsor', false);

        $voucher->fund->fund_config()->update(['allow_physical_cards' => true]);
        $this->assignPhysicalCard($voucher);
    }

    /**
     * @param Voucher $voucher
     * @param string $as
     * @param bool $assert
     * @throws Exception
     * @return void
     */
    protected function assignPhysicalCard(
        Voucher $voucher,
        string $as = 'sponsor',
        bool $assert = true
    ): void {
        $identity = $as === 'sponsor' ? $voucher->fund->organization->identity : $voucher->identity;
        $headers = $this->makeApiHeaders($this->makeIdentityProxy($identity));
        $url = $as === 'sponsor'
            ? $this->getSponsorApiUrlPhysicalCards($voucher)
            : $this->getIdentityApiUrl($voucher, '/physical-cards');

        $code = $this->getPhysicalCardCode();
        $response = $this->post($url, compact('code'), $headers);

        $exist = $voucher->physical_cards()->where('code', $code)->exists();

        if ($assert) {
            $response->assertSuccessful();
            $this->assertTrue($exist, 'Voucher assign physical card failed');
        } else {
            $response->assertForbidden();
            $this->assertFalse($exist, 'Voucher assign physical card failed');
        }
    }

    /**
     * @param Voucher $voucher
     * @throws Exception
     * @return void
     */
    protected function assertAbilityRemovePhysicalCard(Voucher $voucher): void
    {
        /** @var PhysicalCard $card */
        $card = $voucher->physical_cards()->first();
        $this->assertNotNull($card);

        $headers = $this->makeApiHeaders($voucher->fund->organization->identity);
        $url = $this->getSponsorApiUrlPhysicalCards($voucher, "/$card->id");

        $response = $this->delete($url, [], $headers);
        $response->assertSuccessful();

        $card = $voucher->physical_cards()->where('physical_cards.id', $card->id)->first();
        $this->assertNull($card, 'Voucher remove physical card failed');
    }

    /**
     * @param Voucher $voucher
     * @throws Exception
     * @return void
     */
    protected function assertAbilityCreatePhysicalCardRequest(Voucher $voucher): void
    {
        $startDate = now();
        $headers = $this->makeApiHeaders($voucher->fund->organization->identity);

        $url = sprintf(
            $this->apiOrganizationUrl . '/sponsor/vouchers/%s/physical-card-requests',
            $voucher->fund->organization->id,
            $voucher->token_without_confirmation->address
        );

        $response = $this->post($url, [
            'city' => Str::limit($this->faker()->city, 0, 15),
            'house' => $this->faker()->numberBetween(1, 200),
            'address' => $this->faker()->address,
            'postcode' => $this->faker()->postcode,
            'house_addition' => $this->faker()->word,
        ], $headers);

        $response->assertSuccessful();

        $request = $voucher->physical_card_requests()
            ->where('created_at', '>=', $startDate)
            ->first();

        $this->assertNotNull($request, 'Voucher physical request creation failed');
    }

    /**
     * @throws Exception
     * @return string
     */
    protected function getPhysicalCardCode(): string
    {
        do {
            $code = '100' . random_int(111111111, 999999999);
        } while (PhysicalCard::whereCode($code)->exists());

        return $code;
    }

    /**
     * @param Builder $query
     * @param Carbon $startDate
     * @param array $vouchers
     * @param array $assert
     * @throws Exception
     * @return void
     */
    protected function checkAsIdentity(
        Builder $query,
        Carbon $startDate,
        array $vouchers,
        array $assert
    ): void {
        $createdVouchers = $query->get();
        $this->assertEquals(count($vouchers), $createdVouchers->count());

        foreach ($vouchers as $voucherArr) {
            /** @var Voucher $voucher */
            $voucher = $createdVouchers->first(fn (Voucher $item) => $item->note === $voucherArr['note']);
            $this->assertNotNull($voucher);

            if ($voucher->identity) {
                if ($voucher->isBudgetType()) {
                    Cache::clear();

                    $this->assertAbilityIdentityAssignPhysicalCard($voucher);
                    $this->assertAbilityIdentityRemovePhysicalCard($voucher);
                    $this->assertAbilityIdentityCreatePhysicalCardRequest($voucher);
                }

                $this->assertAbilityIdentityShareVoucher($voucher);
                $this->assertAbilityIdentitySendVoucherEmail($voucher);
            }
        }
    }

    /**
     * @param Voucher $voucher
     * @throws Exception
     * @return void
     */
    protected function assertAbilityIdentityAssignPhysicalCard(Voucher $voucher): void
    {
        $voucher->refresh();

        $voucher->fund->fund_config()->update(['allow_physical_cards' => false]);
        $this->assignPhysicalCard($voucher, 'identity', false);

        $voucher->fund->fund_config()->update(['allow_physical_cards' => true]);
        $this->assignPhysicalCard($voucher, 'identity');
    }

    /**
     * @param Voucher $voucher
     * @throws Exception
     * @return void
     */
    protected function assertAbilityIdentityRemovePhysicalCard(Voucher $voucher): void
    {
        $voucher->refresh();

        /** @var PhysicalCard $card */
        $card = $voucher->physical_cards()->first();
        $this->assertNotNull($card);

        $headers = $this->makeApiHeaders($this->makeIdentityProxy($voucher->identity));
        $url = $this->getIdentityApiUrl($voucher, "/physical-cards/$card->id");

        $response = $this->delete($url, [], $headers);
        $response->assertSuccessful();

        $card = $voucher->physical_cards()->where('physical_cards.id', $card->id)->first();
        $this->assertNull($card, 'Voucher remove physical card failed');
    }

    /**
     * @param Voucher $voucher
     * @throws Exception
     * @return void
     */
    protected function assertAbilityIdentityCreatePhysicalCardRequest(Voucher $voucher): void
    {
        $voucher->refresh();

        $startDate = now();
        $headers = $this->makeApiHeaders($this->makeIdentityProxy($voucher->identity));
        $url = $this->getIdentityApiUrl($voucher, '/physical-card-requests');

        $response = $this->post($url, [
            'address' => $this->faker()->address,
            'house' => $this->faker()->numberBetween(1, 100),
            'house_addition' => $this->faker()->word,
            'postcode' => $this->faker()->postcode,
            'city' => Str::limit($this->faker()->city, 0, 15),
        ], $headers);

        $response->assertSuccessful();

        $request = $voucher->physical_card_requests()
            ->where('created_at', '>=', $startDate)
            ->first();

        $this->assertNotNull($request, 'Voucher physical request creation failed');
    }

    /**
     * @param Voucher $voucher
     * @return void
     */
    protected function assertAbilityIdentityShareVoucher(Voucher $voucher): void
    {
        $voucher->refresh();

        if ($voucher->identity->email && $voucher->isProductType()) {
            $startDate = now();
            $headers = $this->makeApiHeaders($this->makeIdentityProxy($voucher->identity));
            $url = $this->getIdentityApiUrl($voucher, '/share');

            $response = $this->post($url, [
                'reason' => $this->faker()->sentence(),
                'send_copy' => true,
            ], $headers);
            $response->assertSuccessful();

            $this->assertMailableSent($voucher->identity->email, ShareProductVoucherMail::class, $startDate);

            if ($providerEmail = $voucher->product->organization->email) {
                $this->assertMailableSent($providerEmail, ShareProductVoucherMail::class, $startDate);
            }
        }
    }

    /**
     * @param Voucher $voucher
     * @return void
     */
    protected function assertAbilityIdentitySendVoucherEmail(Voucher $voucher): void
    {
        $voucher->refresh();

        if ($voucher->identity->email) {
            $startDate = now();
            $headers = $this->makeApiHeaders($this->makeIdentityProxy($voucher->identity));
            $url = $this->getIdentityApiUrl($voucher, '/send-email');

            $response = $this->post($url, [], $headers);
            $response->assertSuccessful();

            $this->assertMailableSent($voucher->identity->email, SendVoucherMail::class, $startDate);
        }
    }

    /**
     * @param Fund $fund
     * @param string $append
     * @return string
     */
    protected function getApiUrl(Fund $fund, string $append = ''): string
    {
        return sprintf($this->apiUrl, $fund->organization->id) . $append;
    }

    /**
     * @param Voucher $voucher
     * @param string $append
     * @return string
     */
    protected function getSponsorApiUrl(Voucher $voucher, string $append = ''): string
    {
        $organization = $voucher->fund->organization;

        return sprintf(
            $this->apiOrganizationUrl . '/sponsor/vouchers/%s',
            $organization->id,
            $voucher->id
        ) . $append;
    }

    /**
     * @param Voucher $voucher
     * @param string $append
     * @return string
     */
    protected function getSponsorApiUrlPhysicalCards(Voucher $voucher, string $append = ''): string
    {
        $organization = $voucher->fund->organization;

        return sprintf(
            $this->apiBaseUrl . '/sponsor/%s/vouchers/%s/physical-cards',
            $organization->id,
            $voucher->id
        ) . $append;
    }

    /**
     * @param Voucher $voucher
     * @param string $append
     * @return string
     */
    protected function getIdentityApiUrl(Voucher $voucher, string $append = ''): string
    {
        return "$this->apiBaseUrl/vouchers/$voucher->number" . $append;
    }
}
