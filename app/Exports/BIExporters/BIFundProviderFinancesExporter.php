<?php

namespace App\Exports\BIExporters;

use App\Exports\ProviderFinancesExport;
use App\Http\Resources\ProviderFinancialResource;
use App\Models\Organization;
use App\Services\BIConnectionService\Exporters\BaseBIExporter;

class BIFundProviderFinancesExporter extends BaseBIExporter
{
    protected string $key = 'fund_provider_finances';
    protected string $name = 'Aanbieder transacties';

    /**
     * @return array
     */
    public function toArray(): array
    {
        $providers = Organization::searchProviderOrganizations($this->organization, [])
            ->with(ProviderFinancialResource::$load)
            ->get();

        $data = new ProviderFinancesExport($this->organization, $providers);

        return $data->collection()->toArray();
    }
}