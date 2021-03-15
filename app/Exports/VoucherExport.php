<?php

namespace App\Exports;

use App\Models\Organization;
use App\Models\Voucher;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class VoucherExport implements FromCollection, WithHeadings
{
    protected $data;
    protected $request;
    protected $headers;

    public function __construct(Request $request, Organization $organization)
    {
        $this->request = $request;
        $this->data = Voucher::export($this->request, $organization);
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
}