<?php

namespace App\Exports;

use App\Models\Organization;
use App\Models\VoucherTransaction;
use App\Scopes\Builders\VoucherTransactionQuery;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class VoucherTransactionsProviderExport extends BaseVoucherTransactionsExport
{
    /**
     * @var array|string[]
     */
    protected static array $exportFields = [
        'id',
        'amount',
        'method',
        'branch_id',
        'branch_name',
        'branch_number',
        'amount_extra',
        'date_transaction',
        'date_payment',
        'fund_name',
        'product_name',
        'provider',
        'state',
        'amount_extra_cash',
    ];

    /**
     * @param Request $request
     * @param Organization $organization
     * @return \Illuminate\Support\Collection
     */
    protected function export(Request $request, Organization $organization): Collection
    {
        $builder = VoucherTransactionQuery::order(
            VoucherTransaction::searchProvider($request, $organization),
            $request->get('order_by'),
            $request->get('order_dir')
        );

        return $this->exportTransform($builder->with('voucher.fund', 'provider', 'product')->get());
    }
}
