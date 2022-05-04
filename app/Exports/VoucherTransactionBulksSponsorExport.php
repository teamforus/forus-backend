<?php

namespace App\Exports;

use App\Models\Fund;
use App\Models\Organization;
use App\Models\VoucherTransaction;
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
    protected $request;
    protected $data;
    protected $headers;

    protected static $fields = [
        ['id', 'ID'],
        ['amount', 'Bedrag'],
        ['date', 'Datum'],
        ['quantity', 'Aantal'],
        ['state', 'Status'],
    ];

    /**
     * @param Request $request
     * @param Organization $organization
     * @param array $fields
     * @param Organization|null $provider
     */
    public function __construct(
        Request $request,
        Organization $organization,
        array $fields,
        Organization $provider = null
    ) {
        $this->request = $request;

        $this->data = VoucherTransactionBulk::exportSponsor(
            $this->request,
            $organization,
            $provider
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
     * @param string $type
     * @return array
     */
    public static function getExportFieldsList(string $type = 'budget') : array
    {
        return array_filter(array_map(function($row) use ($type) {
            list($key, $name) = $row;

            return compact('key', 'name');
        }, static::$fields));
    }
}
