<?php

namespace App\Exports;

use App\Http\Requests\Api\Platform\Funds\Requests\IndexFundRequestsRequest;
use App\Models\Employee;
use App\Models\FundRequest;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class FundRequestsExport implements FromCollection, WithHeadings
{
    protected $data;
    protected $headers;

    /**
     * FundRequestsExport constructor.
     *
     * @param IndexFundRequestsRequest $request
     * @param Employee $employee
     */
    public function __construct(IndexFundRequestsRequest $request, Employee $employee)
    {
        $this->data = FundRequest::exportSponsor($request, $employee);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Builder[]|\Illuminate\Database\Eloquent\Collection|Collection
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
