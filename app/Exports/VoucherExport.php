<?php

namespace App\Exports;

use App\Models\Data\VoucherExportData;
use Illuminate\Support\Collection;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
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

    /**
     * @param EloquentCollection $vouchers
     * @param array $fieldsList
     */
    public function __construct(EloquentCollection $vouchers, array $fieldsList)
    {
        $voucherData = collect();
        $vouchers->load(
            'transactions', 'voucher_relation', 'product', 'fund',
            'token_without_confirmation', 'identity.primary_email', 'product_vouchers'
        );

        foreach ($vouchers as $voucher) {
            $voucherData->push((new VoucherExportData($voucher, $fieldsList, true))->toArray());
        }

        $this->data = $voucherData;
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
     * @return object[]
     */
    public static function getExportFieldsList() : array {
        return array(
            (object) [
                'name' => 'Toegekend',
                'key'  => 'granted'
            ],
            (object) [
                'name' => 'E-mailadres',
                'key'  => 'identity_email'
            ],
            (object) [
                'name' => 'Aanmaker',
                'key'  => 'source'
            ],
            (object) [
                'name' => 'In gebruik',
                'key'  => 'in_use'
            ],
            (object) [
                'name' => 'Status',
                'key'  => 'state'
            ],
            (object) [
                'name' => 'Bedrag',
                'key'  => 'amount'
            ],
            (object) [
                'name' => 'In gebruik datum',
                'key'  => 'in_use_date'
            ],
            (object) [
                'name' => 'Activatiecode',
                'key'  => 'activation_code'
            ],
            (object) [
                'name' => 'Fondsnaam',
                'key'  => 'fund_name'
            ],
            (object) [
                'name' => 'BSN (door medewerker)',
                'key'  => 'reference_bsn'
            ],
            (object) [
                'name' => 'Uniek nummer',
                'key'  => 'activation_code_uid'
            ],
            (object) [
                'name' => 'Aangemaakt op',
                'key'  => 'created_at'
            ],
            (object) [
                'name' => 'BSN (DigiD)',
                'key'  => 'identity_bsn'
            ],
            (object) [
                'name' => 'Notitie',
                'key'  => 'note'
            ],
            (object) [
                'name' => 'Verlopen op',
                'key'  => 'expire_at'
            ]
        );
    }
}