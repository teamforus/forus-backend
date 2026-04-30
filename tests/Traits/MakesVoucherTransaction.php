<?php

namespace Tests\Traits;

use App\Models\Fund;
use App\Models\Identity;
use App\Models\Organization;
use App\Models\Voucher;
use App\Models\VoucherTransaction;
use App\Scopes\Builders\FundQuery;
use App\Scopes\Builders\VoucherQuery;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Random\RandomException;

trait MakesVoucherTransaction
{
    use MakesTestVouchers;
    use MakesTestFundProviders;

    /**
     * @param Organization $organization
     * @return Voucher|Builder
     */
    public function getVouchersForBatchTransactionsQuery(Organization $organization): Voucher|Builder
    {
        $builder = Voucher::query()
            ->where(fn (Builder $builder) => VoucherQuery::whereNotExpiredAndActive($builder))
            ->whereNull('product_id');

        $builder = VoucherQuery::addBalanceFields($builder);
        $builder = Voucher::query()->fromSub($builder, 'vouchers');

        $builder->where('balance', '>', 0);

        return $builder->whereHas('fund', function (Builder $builder) use ($organization) {
            $builder->whereRelation('fund_config', 'allow_direct_payments', true);

            FundQuery::whereIsInternalConfiguredAndActive($builder->where([
                'organization_id' => $organization->id,
            ]));
        });
    }

    /**
     * @param Fund $fund
     * @param int $count
     * @param float $price
     * @param Identity|null $identity
     * @return Collection|Voucher[]
     */
    protected function makeProductVouchers(
        Fund $fund,
        int $count,
        float $price = 5,
        Identity $identity = null,
    ): Collection|Arrayable {
        $vouchers = collect();
        $products = $this->makeTestProviderWithProducts($count, $price);

        for ($i = 1; $i <= $count; $i++) {
            $product = $products[$i - 1];
            $this->addProductToFund($fund, $product, false);

            $voucher = $this->makeTestProductVoucher($fund, $identity ?? $this->makeIdentity(), [], $product->id);
            $vouchers->push($voucher);
        }

        return $vouchers;
    }

    /**
     * @param Fund $fund
     * @param int $count
     * @param float $price
     * @return Collection|VoucherTransaction[]
     */
    protected function makeTransactions(
        Fund $fund,
        int $count = 5,
        float $price = 5,
        Identity $identity = null
    ): Collection|Arrayable {
        return $this
            ->makeProductVouchers($fund, $count, $price, $identity)
            ->map(function (Voucher $voucher) use ($fund) {
                $employee = $fund->organization->employees[0];

                $params = [
                    'amount' => $voucher->amount,
                    'product_id' => $voucher->product_id,
                    'employee_id' => $employee?->id,
                    'branch_id' => $employee?->office?->branch_id,
                    'branch_name' => $employee?->office?->branch_name,
                    'branch_number' => $employee?->office?->branch_number,
                    'target' => VoucherTransaction::TARGET_PROVIDER,
                    'organization_id' => $voucher->product->organization_id,
                ];

                return $voucher->makeTransaction($params);
            });
    }

    /**
     * @param Voucher $voucher
     * @param bool $assert
     * @throws RandomException
     * @return VoucherTransaction|null
     */
    protected function makeTopUp(Voucher $voucher, bool $assert = true): ?VoucherTransaction
    {
        $startDate = now();
        $organization = $voucher->fund->organization;
        $maxAmount = min([
            $voucher->fund->fund_config->limit_voucher_top_up_amount,
            $voucher->fund->fund_config->limit_voucher_total_amount - $voucher->amount_total,
        ]);

        $amount = $this->makeTransactionAmount((float) $maxAmount);

        $headers = $this->makeApiHeaders($organization->identity);

        $url = "/api/v1/platform/organizations/$organization->id/sponsor/transactions";

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

            return $transaction;
        }

        $response->assertJsonValidationErrors(['target']);

        return null;
    }

    /**
     * @param float $maxAmount
     * @return float
     */
    protected function makeTransactionAmount(float $maxAmount): float
    {
        return max(0.02, floor(($maxAmount / 4) * 100) / 100);
    }
}
