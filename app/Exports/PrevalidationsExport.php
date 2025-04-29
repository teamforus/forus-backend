<?php

namespace App\Exports;

use App\Http\Requests\BaseFormRequest;
use App\Models\Prevalidation;
use App\Searches\PrevalidationSearch;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class PrevalidationsExport implements FromCollection, WithHeadings
{
    protected Collection $data;

    public function __construct(Builder|Relation|Prevalidation $builder)
    {
        $this->data = Prevalidation::export($builder);
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
