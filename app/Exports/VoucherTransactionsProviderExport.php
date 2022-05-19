<?php

namespace App\Exports;

use App\Models\Organization;
use App\Models\VoucherTransaction;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class VoucherTransactionsProviderExport implements FromCollection, WithHeadings
{
    protected $request;
    protected $data;
    protected $headers;

    protected static array $fields = [
        ['id', 'ID'],
        ['amount', 'Bedrag'],
        ['date_transaction', 'Datum transactie'],
        ['date_payment', 'Datum betaling'],
        ['fund_name', 'Fonds'],
        ['provider', 'Aanbieder'],
        ['state', 'Status'],
    ];

    /**
     * VoucherTransactionsProviderExport constructor.
     * @param Request $request
     * @param Organization $organization
     * @param array $fields
     */
    public function __construct(
        Request $request,
        Organization $organization,
        array $fields
    ) {
        $this->request = $request;
        $this->data = VoucherTransaction::exportProvider(
            $this->request,
            $organization,
            $fields
        );
    }

    /**
    * @return \Illuminate\Support\Collection
    */
    public function collection()
    {
        return $this->data;
    }

    /**
     * @return array
     */
    public function headings(): array
    {
        return $this->data->map(function ($row) {
            return array_keys($row);
        })->flatten()->unique()->toArray();
    }

    /**
     * @return array
     */
    public static function getExportFields() : array
    {
        return array_filter(array_map(function($row) {
            list($key, $name) = $row;

            return compact('key', 'name');
        }, static::$fields));
    }
}
