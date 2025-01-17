<?php

namespace App\Exports;

use App\Models\Organization;
use App\Models\VoucherTransactionBulk;
use Illuminate\Http\Request;

class VoucherTransactionBulksExport extends BaseFieldedExport
{
    protected array $fields;

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
        $this->fields = $fields;
    }

    /**
     * @return array
     */
    public function headings(): array
    {
        $collection = $this->collection();

        return array_map(
            fn ($key) => static::$exportFields[$key] ?? $key,
            $collection->isNotEmpty() ? array_keys($collection->first()) : $this->fields
        );
    }
}
