<?php

namespace App\Exports;

use App\Http\Requests\BaseFormRequest;
use App\Models\Prevalidation;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class PrevalidationsExport implements FromCollection, WithHeadings
{
    protected BaseFormRequest $request;
    protected Collection $data;

    public function __construct(BaseFormRequest $request)
    {
        $this->request = $request;
        $this->data = Prevalidation::export($this->request);
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
}
