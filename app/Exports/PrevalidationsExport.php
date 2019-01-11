<?php

namespace App\Exports;

use App\Models\Prevalidation;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class PrevalidationsExport implements FromCollection, WithHeadings
{
    protected $request;
    protected $data;
    protected $headers;

    public function __construct(Request $request)
    {
        $this->request = $request;
        $this->data = Prevalidation::export($this->request);
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
