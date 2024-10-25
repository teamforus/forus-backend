<?php

namespace App\Exports;

use App\Models\Organization;
use App\Models\VoucherTransaction;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class VoucherTransactionsProviderExport extends BaseFieldedExport
{
    protected Collection $data;
    protected array $fields;

    /**
     * @var array|string[]
     */
    protected static array $exportFields = [
        'id' => 'ID',
        'amount' => 'Bedrag',
        'method' => 'Betaalmethode(s)',
        'branch_id' => 'Vestiging ID',
        'branch_name' => 'Vestigingsnaam',
        'branch_number' => 'Vestigingsnummer',
        'amount_extra' => 'Extra betaling',
        'date_transaction' => 'Datum',
        'date_payment' => 'Datum betaling',
        'fund_name' => 'Fonds',
        'product_name' => 'Aanbod naam',
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
