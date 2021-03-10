<?php

namespace App\Exports;

use App\Models\Voucher;
use Illuminate\Http\Request;

class VoucherExport
{
    protected $request;
    protected $data;
    protected $headers;

    public function __construct(Request $request)
    {
        $this->request = $request;
        $this->data = Voucher::export($this->request);
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