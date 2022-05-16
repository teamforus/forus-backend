<?php

namespace App\Exports;

use App\Models\VoucherTransactionBulk;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class VoucherTransactionBulkSponsorExport implements FromCollection, WithHeadings
{
    protected Request $request;
    protected $data;
    protected $headers;

    protected static array $fields = [
        ['id', 'ID'],
        ['amount', 'Bedrag'],
        ['date_transaction', 'Datum'],
        ['fund_name', 'Fonds'],
        ['provider', 'Aanbieder'],
        ['state', 'Status'],
    ];

    /**
     * @param Request $request
     * @param VoucherTransactionBulk $transactionBulk
     * @param array $fields
     */
    public function __construct(
        Request $request,
        VoucherTransactionBulk $transactionBulk,
        array $fields
    ) {
        $this->request = $request;

        $this->data = VoucherTransactionBulk::exportSponsor(
            $this->request,
            $transactionBulk,
            $fields,
        );
    }

    /**
     * @return Collection
     */
    public function collection(): Collection
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
    public static function getExportFieldsList() : array
    {
        return array_filter(array_map(function($row) {
            list($key, $name) = $row;

            return compact('key', 'name');
        }, static::$fields));
    }
}