<?php

namespace App\Exports;

use App\Models\Organization;
use App\Models\VoucherTransaction;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class VoucherTransactionsProviderExport extends BaseFieldedExport
{
    protected Collection $data;

    /**
     * @var array|string[]
     */
    protected static array $exportFields = [
        'id' => 'ID',
        'amount' => 'Bedrag',
        'date_transaction' => 'Datum',
        'date_payment' => 'Datum betaling',
        'fund_name' => 'Fonds',
        'provider' => 'Aanbieder',
        'state' => 'Status',
    ];

    /**
     * @param Request $request
     * @param Organization $organization
     * @param array $fields
     */
    public function __construct(Request $request, Organization $organization, array $fields = [])
    {
        $this->data = VoucherTransaction::exportProvider($request, $organization, $fields);
    }
}
