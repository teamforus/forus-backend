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
     */
    public function __construct(EloquentCollection $vouchers)
    {
        $voucherData = collect();
        $vouchers->load(
            'transactions', 'voucher_relation', 'product', 'fund',
            'token_without_confirmation', 'identity.primary_email', 'product_vouchers',
        );

        foreach ($vouchers as $voucher) {
            $voucherData->push((new VoucherExportData($voucher, true))->toArray());
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
}