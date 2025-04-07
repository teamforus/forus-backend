<?php

namespace App\Exports;

use App\Exports\Base\BaseFieldedExport;
use App\Models\Organization;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Collection;

class ProviderFinancesExport extends BaseFieldedExport
{
    protected static string $transKey = 'finances';

    /**
     * @var array|string[]
     */
    protected static array $exportFields = [
        'provider',
        'business_type',
        'total_amount',
        'highest_transaction',
        'nr_transactions',
    ];

    /**
     * @param EloquentCollection $providers
     * @param array $fields
     */
    public function __construct(EloquentCollection $providers, protected array $fields = [])
    {
        $this->data = $this->export($providers);
    }

    /**
     * @param EloquentCollection $data
     * @return Collection
     */
    protected function export(EloquentCollection $data): Collection
    {
        return $this->exportTransform($data);
    }

    /**
     * @param \Illuminate\Database\Eloquent\Collection $data
     * @return Collection
     */
    protected function exportTransform(Collection $data): Collection
    {
        return $this->transformKeys($data->map(fn (Organization $provider) => array_only(
            $this->getRow($provider),
            $this->fields,
        ))->values());
    }

    /**
     * @param Organization $provider
     * @return array
     */
    protected function getRow(Organization $provider): array
    {
        return [
            'provider' => $provider->name,
            'business_type' => $provider->business_type?->name ?: '-',
            'total_amount' => (string) ($provider->total_spent ?? '0'),
            'highest_transaction' => (string) ($provider->highest_transaction ?? '0'),
            'nr_transactions' => (string) ($provider->nr_transactions ?? '0'),
        ];
    }
}
