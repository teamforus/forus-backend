<?php

namespace App\Exports;

use App\Exports\Base\BaseVoucherTransactionsExport;
use App\Http\Requests\Api\Platform\Organizations\Transactions\BaseIndexTransactionsRequest;
use App\Models\Organization;
use App\Models\VoucherTransaction;
use App\Scopes\Builders\VoucherTransactionQuery;
use Illuminate\Support\Collection;

class VoucherTransactionsSponsorExport extends BaseVoucherTransactionsExport
{
    /**
     * @var array|string[]
     */
    protected static array $exportFields = [
        'id',
        'amount',
        'amount_extra_cash',
        'date_transaction',
        'date_payment',
        'fund_name',
        'product_id',
        'product_name',
        'provider',
        'date_non_cancelable',
        'state',
        'bulk_status_locale',
    ];

    /**
     * @param BaseIndexTransactionsRequest $request
     * @param Organization $organization
     * @return \Illuminate\Support\Collection
     */
    protected function export(BaseIndexTransactionsRequest $request, Organization $organization): Collection
    {
        $builder = VoucherTransactionQuery::order(
            VoucherTransaction::searchSponsor($request, $organization),
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
