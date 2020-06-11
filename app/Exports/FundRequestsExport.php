<?php

namespace App\Exports;

use App\Models\FundRequest;
use App\Models\Organization;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class FundRequestsExport implements FromCollection, WithHeadings
{
    protected $request;
    protected $data;
    protected $headers;

    /**
     * FundRequestsExport constructor.
     *
     * @param Request $request
     * @param Organization $organization
     */
    public function __construct(
        Request $request,
        Organization $organization
    ) {
        $this->request = $request;

        $this->data = FundRequest::exportSponsor(
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
        return $this->data->map(static function ($row) {
            return array_keys($row);
        })->flatten()->unique()->toArray();
    }
}
