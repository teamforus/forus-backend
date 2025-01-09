<?php

namespace Tests\Traits;

use App\Mail\Vouchers\VoucherAssignedBudgetMail;
use App\Mail\Vouchers\VoucherAssignedProductMail;
use App\Mail\Vouchers\VoucherAssignedSubsidyMail;
use App\Models\Fund;
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
use Illuminate\Support\Arr;
use Random\RandomException;

trait VoucherTestTrait
{
    use WithFaker;
    use DoesTesting;
    use AssertsSentEmails;
    use MakesTestIdentities;
    use MakesTestFundProviders;
    use FundFormulaProductTestTrait;

    /**
     * @var string
     */
    protected string $apiOrganizationUrl = '/api/v1/platform/organizations/%s';

    /**
     * @var string
     */
    protected string $apiFundUrl = '/api/v1/platform/funds/%s';

    /**
     * @var Identity[]
     */
    protected array $identities = [];

    /**
     * @param Fund $fund
     * @param array $assert
     * @param Product[] $products
     * @return array
     * @throws RandomException
     * @throws \Throwable
     */
    protected function makeVoucherData(Fund $fund, array $assert, array $products): array
    {
        $range = range(0, Arr::get($assert, 'vouchers_count', 10) - 1);

        return array_reduce($range, function (array $vouchers, $index) use ($fund, $assert, $products) {
            $params = [];
            $amount = random_int(1, $fund->getMaxAmountPerVoucher());
            $voucherType = $assert['type'] ?? 'budget';
            $sameAssignBy = $assert['same_assign_by'] ?? 0;
            $activationCode = $assert['activation_code'] ?? 0;
            $directPayment = Arr::get($assert, 'direct_payment') ? $this->makeDirectPaymentData() : [];
            $exceedVoucherAmountLimit = $assert['exceed_voucher_amount_limit'] ?? false;

            if ($sameAssignBy > $index && count($vouchers)) {
                $params[$assert['assign_by']] = $vouchers[$index - 1][$assert['assign_by']];
            } else {
                $params = $this->assignByFields($assert, $index);
            }

            if ($voucherType === 'budget') {
                $amount = $exceedVoucherAmountLimit ? $fund->getMaxAmountPerVoucher() + 10 : $amount;
            } elseif ($voucherType === 'product') {
                $productId = array_random($products)->id;
            }

            $item = array_merge($params, [
                'activate' => $assert['activate'] ?? true,
                'activation_code' => $activationCode > $index,
                'limit_multiplier' => $voucherType === 'budget' ? random_int(1, 3) : 1,
                'expire_at' => now()->addDays(30)->format('Y-m-d'),
                'note' => $this->faker()->sentence(),
                'amount' => $amount ?? null,
                'product_id' => $productId ?? null,
            ], $directPayment, $this->recordsFields(), $assert['replacement'] ?? []);

            return array_merge($vouchers, [
                Arr::except($item, $assert['except_fields'] ?? []),
            ]);
        }, []);
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
                $identity = $this->makeIdentity(bsn: $this->randomFakeBsn());
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
    protected function makeDirectPaymentData(): array
    {
        return [
            'direct_payment_iban' => $this->faker()->iban('NL'),
            'direct_payment_name' => $this->faker()->firstName . ' ' . $this->faker()->lastName,
        ];
    }

    /**
     * @param Builder|Voucher $query
     * @param Carbon $startDate
     * @param array $vouchers
     * @param array $assert
     * @return void
     * @throws \Throwable
     */
    protected function assertVouchersCreated(
        Builder|Voucher $query,
        Carbon $startDate,
        array $vouchers,
        array $assert
    ): void {
        $sortedVouchers = [];
        $assertActive = $assert['assert_active'] ?? null;
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
            if ($voucherArr['activate'] || $assertActive !== null) {
                $this->assertActivation($voucher, $voucherArr['activate'] || $assertActive);
            }

            // check identity assign
            if ($assertExistingIdentity) {
                $identity = $this->identities[$index] ?? null;
                $this->assertNotNull($identity);

                $this->checkExistingIdentityAssign($identity, $voucher, $startDate, $assert['assign_by']);
            } else {
                match ($assert['assign_by']) {
                    'bsn' => $this->assertAssignedToNewBsn($voucher, $voucherArr),
                    'email' => $this->assertAssignedToNewEmail($voucher, $startDate),
                    'client_uid' => $this->checkAssignedToClientUid($voucher, $voucherArr),
                };
            }

            $this->assertDirectPayment($voucher, $voucherArr, $assert['direct_payment'] ?? false);
            $this->assertFieldsEquals($voucher, $voucherArr);
            $this->assertActivationCode($voucher, $voucherArr);

            if ($assert['assign_by'] === 'email' && $voucher->isBudgetType()) {
                $this->assertFundFormulaProductVouchersCreatedByMainVoucher($voucher);
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
        $type = $assert['assign_by'];
        $sameAssignBy = $assert['same_assign_by'];
        $activationCode = $assert['activation_code'] ?? 0;

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
    protected function assertActivationCode(Voucher $voucher, array $voucherArr): void
    {
        $voucherArr['activation_code']
            ? $this->assertNotNull($voucher->activation_code)
            : $this->assertNull($voucher->activation_code);
    }

    /**
     * @param Voucher $voucher
     * @param string $type
     * @return string|null
     */
    protected function getVoucherAssignedByValue(Voucher $voucher, string $type): ?string
    {
        return match($type) {
            'bsn' => $voucher->identity?->bsn ?? ($voucher->voucher_relation->bsn ?? null),
            'email' => $voucher->identity?->email,
            'client_uid' => $voucher->client_uid,
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
    protected function assertAssignedToNewBsn(Voucher $voucher, array $voucherArr): void
    {
        $this->assertNotNull($voucher->voucher_relation);
        $this->assertEquals($voucherArr['bsn'], $voucher->voucher_relation->bsn);

        $identity = $this->makeIdentity(bsn: $voucher->voucher_relation->bsn);

        $headers = $this->makeApiHeaders($identity);
        $response = $this->post(sprintf($this->apiFundUrl . '/check', $voucher->fund_id), [], $headers);
        $response->assertSuccessful();
        $this->assertNotEmpty($response['vouchers']);

        $voucher->refresh();

        $this->assertNotNull($voucher->identity);
        $this->assertEquals($identity->address, $voucher->identity->address);

        $this->assertActivation($voucher, true);
    }

    /**
     * @param Voucher $voucher
     * @param Carbon $startDate
     * @return void
     */
    protected function assertAssignedToNewEmail(Voucher $voucher, Carbon $startDate): void
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
     * @param bool $active
     * @return void
     */
    protected function assertActivation(Voucher $voucher, bool $active): void
    {
        $this->assertTrue($active ? $voucher->isActivated() : !$voucher->isActivated());
    }

    /**
     * @param Voucher $voucher
     * @param array $voucherArr
     * @param bool $assertTransaction
     * @return void
     */
    protected function assertDirectPayment(
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
    protected function assertFieldsEquals(
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
     * @param int $fundId
     * @return Fund
     */
    protected function findFund(int $fundId): Fund
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
        return TestData::randomFakeBsn();
    }
}
