<?php

namespace App\Exports;

use App\Models\Organization;
use App\Models\VoucherTransactionBulk;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Illuminate\Support\Collection;

/**
 * Class VoucherTransactionsSponsorExport
 * @package App\Exports
 */
class VoucherTransactionBulksSponsorExport implements FromCollection, WithHeadings
{
    protected Request $request;
    protected $data;
    protected $headers;

    protected static array $fields = [
        ['id', 'ID'],
        ['quantity', 'Aantal'],
        ['amount', 'Bedrag'],
        ['bank_name', 'Bank naam'],
        ['date_transaction', 'Datum transactie'],
        ['state', 'Status'],
    ];

    /**
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

        $this->data = VoucherTransactionBulk::exportSponsor(
            $this->request,
            $organization,
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
    public static function getExportFields() : array
    {
        return array_filter(array_map(function($row) {
            list($key, $name) = $row;

            return compact('key', 'name');
        }, static::$fields));
    }
}
