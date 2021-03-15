<?php

namespace App\Exports;

use App\Models\FundProvider;
use App\Models\Organization;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

/**
 * Class FundProvidersExport
 * @package App\Exports
 */
class FundProvidersExport implements FromCollection, WithHeadings
{
    protected $request;
    protected $data;
    protected $headers;

    /**
     * FundProvidersExport constructor.
     * @param Request $request
     * @param Organization $organization
     * @param Builder|null $builder
     */
    public function __construct(
        Request $request,
        Organization $organization,
        Builder $builder = null
    ) {
        $this->request = $request;
        $this->data = FundProvider::export($this->request, $organization, $builder);
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
