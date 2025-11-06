<?php

namespace App\Exports\BIExporters;

use App\Exports\ProviderFinancesExport;
use App\Models\Organization;
use App\Searches\OrganizationSearch;
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
        $search = new OrganizationSearch([], Organization::query());
        $builder = $search->searchProviderOrganizations($this->organization);
        $export = new ProviderFinancesExport($builder, ProviderFinancesExport::getExportFieldsRaw());

        return $export->collection()->toArray();
    }
}
