<?php

namespace App\Exports;

use App\Exports\Base\BaseVoucherTransactionsExport;
use App\Http\Requests\Api\Platform\Organizations\Transactions\BaseIndexTransactionsRequest;
use App\Models\Organization;
use App\Models\VoucherTransaction;
use App\Scopes\Builders\VoucherTransactionQuery;
use Illuminate\Support\Collection;

class VoucherTransactionsProviderExport extends BaseVoucherTransactionsExport
{
    /**
     * @var array|string[]
     */
    protected static array $exportFields = [
        'id',
        'amount',
        'amount_extra',
        'amount_extra_cash',
        'method',
        'branch_id',
        'branch_name',
        'branch_number',
        'date_transaction',
        'date_payment',
        'fund_name',
        'product_name',
        'provider',
        'state',
        'notes_provider',
        'reservation_code',
    ];

    /**
     * @param BaseIndexTransactionsRequest $request
     * @param Organization $organization
     * @return \Illuminate\Support\Collection
     */
    protected function export(BaseIndexTransactionsRequest $request, Organization $organization): Collection
    {
        $builder = VoucherTransactionQuery::order(
            VoucherTransaction::searchProvider($request, $organization),
            $request->get('order_by'),
            $request->get('order_dir')
        );

        $builder->with([
            'product',
            'provider',
            'voucher.fund',
            'notes_provider',
            'product_reservation',
        ]);

        return $this->exportTransform($builder->get());
    }
}
