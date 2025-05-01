<?php

namespace Tests\Traits;

use App\Models\Fund;
use App\Models\Organization;
use App\Models\Voucher;
use App\Models\VoucherTransaction;
use App\Scopes\Builders\FundQuery;
use App\Scopes\Builders\VoucherQuery;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

trait MakesVoucherTransaction
{
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
                'type' => Fund::TYPE_BUDGET,
                'organization_id' => $organization->id,
            ]));
        });
    }

    /**
     * @param Fund $fund
     * @param int $count
     * @return Collection|Voucher[]
     */
    protected function makeProductVouchers(Fund $fund, int $count): Collection|Arrayable
    {
        $vouchers = collect();
        $products = $this->makeProductsFundFund($count, 5);

        for ($i = 1; $i <= $count; $i++) {
            $product = $products[$i - 1];
            $this->addProductFundToFund($fund, $product, false);

            $voucher = $fund->makeProductVoucher($this->makeIdentity(), [], $product->id);
            $vouchers->push($voucher);
        }

        return $vouchers;
    }

    /**
     * @param Fund $fund
     * @param int $count
     * @return Collection|VoucherTransaction[]
     */
    protected function makeTransactions(Fund $fund, int $count = 5): Collection|Arrayable
    {
        return $this
            ->makeProductVouchers($fund, $count)
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
}
