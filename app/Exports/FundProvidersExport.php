<?php

namespace App\Exports;

use App\Models\FundProvider;
use App\Models\Organization;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class FundProvidersExport implements FromCollection, WithHeadings
{
    protected $request;
    protected $data;
    protected $headers;

    public function __construct(
        Request $request,
        Organization $organization
    ) {
        $this->request = $request;
        $this->data = FundProvider::export(
            $this->request,
            $organization
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
}
