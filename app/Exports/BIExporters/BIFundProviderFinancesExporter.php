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
            ->with(ProviderFinancialResource::load())
            ->get();

        $fields = ProviderFinancesExport::getExportFieldsRaw();
        $data = new ProviderFinancesExport($providers, $fields);

        return $data->collection()->toArray();
    }
}
