<?php

namespace App\Exports;

use App\Models\Organization;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class ProviderFinancesExport implements FromCollection, WithHeadings
{
    protected Collection $data;

    public function __construct(Organization $sponsor, EloquentCollection $providers)
    {
        $this->data = $this->exportTransform($sponsor, $providers);
    }

    /**
     * @param Organization $sponsor
     * @param \Illuminate\Database\Eloquent\Collection $providers
     * @return Collection
     */
    protected function exportTransform(Organization $sponsor, Collection $providers): Collection
    {
        return $providers->map(function(Organization $provider) use ($sponsor) {
            return [
                $this->trans("provider")             => $provider->name,
                $this->trans("business_type")        => $provider->business_type?->name ?: '-',
                $this->trans("total_amount")         => (string) ($provider->total_spent ?? '0'),
                $this->trans("highest_transaction")  => (string) ($provider->highest_transaction ?? '0'),
                $this->trans("nr_transactions")      => (string) ($provider->nr_transactions ?? '0'),
            ];
        })->values();
    }

    /**
     * @param string $key
     * @return string|null
     */
    protected function trans(string $key): ?string
    {
        return trans("export.finances.$key");
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
