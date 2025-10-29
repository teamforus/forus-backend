<?php

namespace App\Exports;

use App\Exports\Base\BaseVoucherTransactionsExport;

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
}
