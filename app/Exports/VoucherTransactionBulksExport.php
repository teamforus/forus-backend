<?php

namespace App\Exports;

use App\Models\Organization;
use App\Models\VoucherTransactionBulk;
use Illuminate\Http\Request;

/**
 * Class VoucherTransactionsSponsorExport
 * @package App\Exports
 */
class VoucherTransactionBulksExport extends BaseFieldedExport
{
    /**
     * @var array|string[]
     */
    public static array $exportFields = [
        'id' => 'ID',
        'state' => 'Status',
        'amount' => 'Bedrag',
        'quantity' => 'Aantal',
        'bank_name' => 'Bank naam',
        'date_transaction' => 'Datum transactie',
    ];

    /**
     * @param Request $request
     * @param Organization $organization
     * @param array $fields
     */
    public function __construct(Request $request, Organization $organization, array $fields)
    {
        $this->data = VoucherTransactionBulk::export($request, $organization, $fields);
    }
}
