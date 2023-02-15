<?php

namespace Tests\Feature;

use App\Models\Implementation;
use App\Models\Organization;
use App\Models\Voucher;
use App\Models\VoucherTransaction;
use App\Scopes\Builders\VoucherQuery;
use App\Searches\VoucherTransactionsSearch;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use Tests\Traits\MakesVoucherTransaction;

class VoucherTransactionBatchTest extends TestCase
{
    use MakesVoucherTransaction, WithFaker;

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
    protected int $transactionCountPerVoucher = 10;

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

        $vouchers = $this->getVouchersQuery($organization)->take($this->vouchersCount)->get();
        $this->assertNotEmpty($vouchers);

        $transactions = $vouchers->reduce(function ($arr, Voucher $voucher) {
            $amount = $voucher->amount_available / $this->transactionCountPerVoucher;

            for ($i = 1; $i <= $this->transactionCountPerVoucher; $i++) {
                $arr[] = array_merge($this->getDefaultValidData(), [
                    'amount' => $amount,
                    'voucher_id' => $voucher->id,
                ]);
            }

            return $arr;
        }, []);

        $this->checkTransactionBatch(compact('transactions'));
    }

    /**
     * @return void
     * @throws \Exception
     */
    public function testVoucherTransactionBatchBigData(): void
    {
        $organization = $this->getOrganization();

        $voucher = $this->getVouchersQuery($organization)->first();
        $this->assertNotNull($voucher);

        $transactions = [];
        $errors = [];
        $rows = 3000;
        $totalAmount = 0;
        $amount = 1;

        $arr = array_merge($this->getDefaultValidData(), [
            'amount' => $amount,
            'voucher_id' => $voucher->id,
        ]);

        foreach (range(0, $rows) as $item) {
            $totalAmount += $amount;
            $transactions[] = $arr;

            if ($voucher->amount_available_cached < $totalAmount) {
                $errors[] = "transactions.{$item}.amount";
            }
        }

        $this->checkTransactionBatch(compact('transactions'), $errors);
    }

    /**
     * @return void
     * @throws \Exception
     */
    public function testVoucherTransactionBatchWithInvalidVoucher(): void
    {
        $organization = $this->getOrganization();

        /** @var Voucher|null $voucher */
        $voucher = Voucher::whereNotIn(
            'id', $this->getVouchersQuery($organization)->select('id')
        )->first();
        $this->assertNotNull($voucher);

        $transactions = [
            array_merge($this->getDefaultValidData(), [
                'amount' => $voucher->amount_available,
                'voucher_id' => $voucher->id,
            ])
        ];

        $errors = [
            'transactions.0.voucher_id',
        ];

        $this->checkTransactionBatch(compact('transactions'), $errors);
    }

    /**
     * @return void
     * @throws \Exception
     */
    public function testVoucherTransactionBatchWithInvalidSingleAmount(): void
    {
        $organization = $this->getOrganization();

        $voucher = $this->getVouchersQuery($organization)->first();
        $this->assertNotNull($voucher);

        $transactions = [
            array_merge($this->getDefaultValidData(), [
                'amount' => 0.01,
                'voucher_id' => $voucher->id,
            ]),
            array_merge($this->getDefaultValidData(), [
                'amount' => floatval($voucher->amount_available) + 100,
                'voucher_id' => $voucher->id,
            ]),
        ];

        $errors = [
            'transactions.0.amount',
            'transactions.1.amount',
        ];

        $this->checkTransactionBatch(compact('transactions'), $errors);
    }

    /**
     * @return void
     * @throws \Exception
     */
    public function testVoucherTransactionBatchWithInvalidMultipleAmount(): void
    {
        $organization = $this->getOrganization();

        $vouchers = $this->getVouchersQuery($organization)->take($this->vouchersCount)->get();
        $this->assertNotEmpty($vouchers);

        $transactions = $vouchers->reduce(function ($arr, Voucher $voucher) {
            $amount = $voucher->amount_available / $this->transactionCountPerVoucher;

            for ($i = 1; $i <= $this->transactionCountPerVoucher; $i++) {
                $amount += $this->transactionCountPerVoucher === $i ? 100 : 0;

                $arr[] = array_merge($this->getDefaultValidData(), [
                    'amount' => $amount,
                    'voucher_id' => $voucher->id,
                ]);
            }

            return $arr;
        }, []);

        $errors = $vouchers->reduce(function (array $arr, Voucher $voucher, int $key) {
            return array_merge($arr, [
                'transactions.' . ($key + 1) * $this->transactionCountPerVoucher - 1 . '.amount'
            ]);
        }, []);

        $this->checkTransactionBatch(compact('transactions'), $errors);
    }

    /**
     * @return void
     * @throws \Exception
     */
    public function testVoucherTransactionBatchWithInvalidData(): void
    {
        $organization = $this->getOrganization();

        $voucher = $this->getVouchersQuery($organization)->first();
        $this->assertNotNull($voucher);

        $transactions = [
            array_merge($this->getDefaultValidData(), [
                'uid' => Str::random(50),
                'amount' => $voucher->amount_available,
                'voucher_id' => $voucher->id,
                'note' => [],
                'direct_payment_iban' => '',
                'direct_payment_name' => '',
            ])
        ];

        $errors = [
            'transactions.0.uid',
            'transactions.0.note',
            'transactions.0.direct_payment_iban',
            'transactions.0.direct_payment_name',
        ];

        $this->checkTransactionBatch(compact('transactions'), $errors);
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
            array_merge($this->getDefaultValidData(), [
                'amount' => $voucher->amount_available,
                'voucher_id' => $voucher->id,
            ])
        ];

        $errors = [
            'transactions.0.voucher_id',
        ];

        $this->checkTransactionBatch(compact('transactions'), $errors);
    }

    /**
     * @return array
     */
    private function getDefaultValidData(): array
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
     * @param array $data
     * @param array $errors
     * @return void
     */
    private function checkTransactionBatch(array $data, array $errors = []): void
    {
        $startDate = now();
        $organization = $this->getOrganization();

        $identity = $organization->identity;
        $this->assertNotNull($identity);

        $headers = $this->makeApiHeaders($this->makeIdentityProxy($identity));

        // validate
        $response = $this->post(sprintf($this->apiUrl, $organization->id) . '/validate', $data, $headers);
        count($errors) ? $response->assertJsonValidationErrors($errors) : $response->assertSuccessful();

        // store
        $response = $this->post(sprintf($this->apiUrl, $organization->id), $data, $headers);

        if (count($errors) > 0) {
            $response->assertJsonValidationErrors($errors);
        } else {
            $response
                ->assertSuccessful()
                ->assertJsonStructure([
                    'created'
                ]);

            // check transactions
            $query = VoucherTransaction::query()->whereIn(
                'voucher_id', array_unique(Arr::pluck($data['transactions'], 'voucher_id'))
            )->where('created_at', '>=', $startDate);

            $builder = new VoucherTransactionsSearch([], $query);
            $transactions = $builder->searchSponsor($organization)->get();

            $this->assertEquals(count($data['transactions']), $transactions->count());
            $this->assertEquals(
                array_sum(Arr::pluck($data['transactions'], 'amount')),
                $transactions->sum('amount')
            );
        }
    }
}
