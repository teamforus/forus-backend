<?php

namespace App\Exports;

use App\Exports\Base\BaseExport;
use App\Http\Resources\ProviderFinancialResource;
use App\Models\Organization;
use Illuminate\Database\Eloquent\Model;

class ProviderFinancesExport extends BaseExport
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
     * @param Model|Organization $model
     * @return array
     */
    protected function getRow(Model|Organization $model): array
    {
        return [
            'provider' => $model->name,
            'business_type' => $model->business_type?->name ?: '-',
            'total_amount' => (string) ($model->total_spent ?? '0'),
            'highest_transaction' => (string) ($model->highest_transaction ?? '0'),
            'nr_transactions' => (string) ($model->nr_transactions ?? '0'),
        ];
    }

    /**
     * @return array
     */
    protected function getBuilderWithArray(): array
    {
        return ProviderFinancialResource::$load;
    }
}
