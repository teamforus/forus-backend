<?php

namespace Tests\Traits;

use App\Models\Fund;
use App\Models\Voucher;
use App\Models\Organization;
use App\Scopes\Builders\FundQuery;
use App\Scopes\Builders\VoucherQuery;
use Illuminate\Database\Eloquent\Builder;

trait MakesVoucherTransaction
{
    /**
     * @param Organization $organization
     * @return Voucher|Builder
     */
    public function getVouchersForBatchTransactionsQuery(
        Organization $organization,

    ): Voucher|Builder {
        $builder = Voucher::query()
            ->where(fn (Builder $builder) => VoucherQuery::whereNotExpiredAndActive($builder))
            ->whereNull('product_id');

        $builder = VoucherQuery::addBalanceFields($builder);
        $builder = Voucher::query()->fromSub($builder, 'vouchers');

        $builder->where('balance', '>', 0);

        return $builder->whereHas('fund', function(Builder $builder) use ($organization) {
            $builder->whereRelation('fund_config', 'allow_direct_payments', true);

            FundQuery::whereIsInternalConfiguredAndActive($builder->where([
                'type' => Fund::TYPE_BUDGET,
                'organization_id' => $organization->id,
            ]));
        });
    }
}