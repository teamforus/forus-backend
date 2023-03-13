<?php

namespace Tests\Feature;

use App\Models\Implementation;
use App\Models\Organization;
use App\Models\Voucher;
use App\Models\VoucherTransaction;
use App\Scopes\Builders\VoucherQuery;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use Tests\Traits\MakesVoucherTransaction;

class VoucherTransactionBatchTest extends TestCase
{
    use MakesVoucherTransaction, WithFaker, DatabaseTransactions;

    /**
     * @var string
     */
    protected string $apiUrl = '/api/v1/platform/organizations/%s/sponsor/transactions/batch';

    /**
     * @var string
     */
    protected string $implementationName = 'nijmegen';

    /**
     * @var int
     */
    protected int $transactionsPerVoucher = 10;

    /**
     * @var int
     */
    protected int $vouchersCount = 3;

    /**
     * @return void
     * @throws \Exception
     */
    public function testVoucherTransactionBatchWithValidData(): void
    {
        $organization = $this->getOrganization();
        $vouchers = $this->getVouchersForBatchTransactionsQuery($organization)->take($this->vouchersCount)->get();
        $this->assertNotEmpty($vouchers);

        $transactions = $vouchers->reduce(function (array $arr, Voucher $voucher) {
            return array_merge($arr, array_map(fn () => array_merge([
                'amount' => $voucher->amount_available / $this->transactionsPerVoucher,
                'voucher_id' => $voucher->id,
            ], $this->getDefaultTransactionData()), range(1, $this->transactionsPerVoucher)));
        }, []);

        $this->checkTransactionBatch($transactions);
    }

    /**
     * @return void
     * @throws \Exception
     */
    public function testVoucherTransactionsBatchWithInvalidTotalAmount(): void
    {
        $organization = $this->getOrganization();
        $voucher = $this->getVouchersForBatchTransactionsQuery($organization)->first();
        $this->assertNotNull($voucher);

        $transactions = [];
        $errors = [];
        $rows = 3000;

        foreach (range(0, $rows) as $item) {
            $transactions[] = array_merge($this->getDefaultTransactionData(), [
                'amount' => 1,
                'voucher_id' => $voucher->id,
            ]);

            if ($voucher->amount_available_cached < array_sum(array_pluck($transactions, 'amount'))) {
                $errors[] = "transactions.$item.amount";
            }
        }

        $this->checkTransactionBatch($transactions, $errors);
    }

    /**
     * @return void
     * @throws \Exception
     */
    public function testVoucherTransactionBatchWithInvalidVoucher(): void
    {
        $organization = $this->getOrganization();

        /** @var Voucher|null $voucher */
        $vouchersQuery = $this->getVouchersForBatchTransactionsQuery($organization);
        $voucher = Voucher::whereNotIn('id', $vouchersQuery->select('id'))->first();
        $this->assertNotNull($voucher);

        $transaction = array_merge($this->getDefaultTransactionData(), [
            'amount' => $voucher->amount_available,
            'voucher_id' => $voucher->id,
        ]);

        $this->checkTransactionBatch([$transaction], [
            'transactions.0.voucher_id',
        ]);
    }

    /**
     * @return void
     * @throws \Exception
     */
    public function testVoucherTransactionBatchWithInvalidAmount(): void
    {
        $organization = $this->getOrganization();

        $voucher = $this->getVouchersForBatchTransactionsQuery($organization)->first();
        $this->assertNotNull($voucher);

        $transactions = [
            array_merge($this->getDefaultTransactionData(), [
                'amount' => 0.01,
                'voucher_id' => $voucher->id,
            ]),
            array_merge($this->getDefaultTransactionData(), [
                'amount' => 0.02,
                'voucher_id' => $voucher->id,
            ]),
            array_merge($this->getDefaultTransactionData(), [
                'amount' => floatval($voucher->amount_available) + 100,
                'voucher_id' => $voucher->id,
            ]),
        ];

        $this->checkTransactionBatch($transactions, [
            'transactions.0.amount',
            'transactions.2.amount',
        ]);
    }

    /**
     * @return void
     * @throws \Exception
     */
    public function testVoucherTransactionBatchWithInvalidMultipleAmounts(): void
    {
        $organization = $this->getOrganization();
        $vouchers = $this->getVouchersForBatchTransactionsQuery($organization)->take($this->vouchersCount)->get();
        $this->assertNotEmpty($vouchers);

        $transactions = $vouchers->reduce(function (array $arr, Voucher $voucher) {
            return array_merge($arr, array_map(fn ($index) => array_merge([
                'amount' => array_sum([
                    $voucher->amount_available / $this->transactionsPerVoucher,
                    $index == $this->transactionsPerVoucher ? 100 : 0,
                ]),
                'voucher_id' => $voucher->id,
            ], $this->getDefaultTransactionData()), range(1, $this->transactionsPerVoucher)));
        }, []);

        $errors = $vouchers->keys()->map(function ($key) {
            return 'transactions.' . ($key + 1) * $this->transactionsPerVoucher - 1 . '.amount';
        })->toArray();

        $this->checkTransactionBatch($transactions, $errors);
    }

    /**
     * @return void
     * @throws \Exception
     */
    public function testVoucherTransactionBatchWithInvalidData(): void
    {
        $organization = $this->getOrganization();
        $voucher = $this->getVouchersForBatchTransactionsQuery($organization)->first();
        $this->assertNotNull($voucher);

        $transactions = [
            array_merge($this->getDefaultTransactionData(), [
                'uid' => Str::random(50),
                'amount' => $voucher->amount_available,
                'voucher_id' => $voucher->id,
                'note' => [],
                'direct_payment_iban' => '',
                'direct_payment_name' => '',
            ])
        ];

        $this->checkTransactionBatch($transactions, [
            'transactions.0.uid',
            'transactions.0.note',
            'transactions.0.direct_payment_iban',
            'transactions.0.direct_payment_name',
        ]);
    }

    /**
     * @return void
     * @throws \Exception
     */
    public function testVoucherTransactionBatchWithInvalidFund(): void
    {
        $organization = Organization::query()->whereDoesntHave('funds', function(Builder $builder) {
            $builder->whereRelation('fund_config', 'allow_direct_payments', true);
        })->first();

        $this->assertNotNull($organization);

        $voucher = Voucher::query()
            ->where(fn (Builder $builder) => VoucherQuery::whereNotExpiredAndActive($builder))
            ->whereNull('product_id')
            ->first();

        $this->assertNotNull($voucher);

        $transactions = [
            array_merge($this->getDefaultTransactionData(), [
                'amount' => $voucher->amount_available,
                'voucher_id' => $voucher->id,
            ])
        ];

        $errors = [
            'transactions.0.voucher_id',
        ];

        $this->checkTransactionBatch($transactions, $errors);
    }

    /**
     * @return array
     */
    private function getDefaultTransactionData(): array
    {
        return [
            'uid' => Str::random(15),
            'note' => $this->faker()->sentence(),
            'direct_payment_iban' => $this->faker()->iban('NL'),
            'direct_payment_name' => $this->faker()->firstName . ' ' . $this->faker()->lastName,
        ];
    }

    /**
     * @return Organization|null
     */
    private function getOrganization(): ?Organization
    {
        $implementation = Implementation::byKey($this->implementationName);
        $this->assertNotNull($implementation);
        $this->assertNotNull($implementation->organization);

        return $implementation->organization;
    }

    /**
     * @param array $transactions
     * @param array $errors
     * @return void
     */
    private function checkTransactionBatch(array $transactions, array $errors = []): void
    {
        $startDate = now();
        $organization = $this->getOrganization();
        $headers = $this->makeApiHeaders($this->makeIdentityProxy($organization->identity));
        $data = compact('transactions');

        // validate
        $validateResponse = $this->post(sprintf($this->apiUrl, $organization->id) . '/validate', $data, $headers);
        $uploadResponse = $this->post(sprintf($this->apiUrl, $organization->id), $data, $headers);

        if (count($errors) > 0) {
            $validateResponse->assertJsonValidationErrors($errors);
            $uploadResponse->assertJsonValidationErrors($errors);
        } else {
            $validateResponse->assertSuccessful();
            $uploadResponse->assertSuccessful()->assertJsonStructure(['created']);

            // check transactions
            $createdTransactions = VoucherTransaction::query()
                ->whereIn('voucher_id', array_unique(Arr::pluck($transactions, 'voucher_id')))
                ->where('created_at', '>=', $startDate)
                ->whereRelation('voucher.fund', 'organization_id', $organization->id)
                ->get();

            $this->assertEquals(count($transactions), $createdTransactions->count());
            $this->assertEquals(array_sum(Arr::pluck($transactions, 'amount')), $createdTransactions->sum('amount'));
        }
    }
}
