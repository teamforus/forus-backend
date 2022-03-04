<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

/**
 * Class VoucherExport
 * @package App\Exports
 */
class VoucherExport implements FromCollection, WithHeadings
{
    protected $data;
    protected $request;
    protected $headers;

    protected static $fields = [
        ['granted', 'Toegekend'],
        ['identity_email', 'E-mailadres'],
        ['in_use', 'In gebruik'],
        ['state', 'Status'],
        ['amount', 'Bedrag'],
        ['in_use_date', 'In gebruik datum'],
        ['activation_code', 'Activatiecode'],
        ['fund_name', 'Fondsnaam'],
        ['reference_bsn', 'BSN (door medewerker)'],
        ['activation_code_uid', 'Uniek nummer'],
        ['created_at', 'Aangemaakt op'],
        ['identity_bsn', 'BSN (DigiD)'],
        ['source', 'Aangemaakt door'],
        ['product_name', 'Aanbod naam'],
        ['note', 'Notitie'],
        ['expire_at', 'Verlopen op'],
    ];

    protected static $productVoucherOnlyKeys = [
        'product_name',
    ];

    /**
     * @param array $data
     */
    public function __construct(array $data)
    {
        $this->data = collect($data);
    }

    /**
     * @return \Illuminate\Support\Collection
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

            if ($type !== 'product' && in_array($key, static::$productVoucherOnlyKeys)) {
                return null;
            }

            return compact('key', 'name');
        }, static::$fields));
    }
}