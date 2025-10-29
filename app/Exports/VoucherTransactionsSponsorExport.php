<?php

namespace App\Exports;

use App\Exports\Base\BaseVoucherTransactionsExport;

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
}
