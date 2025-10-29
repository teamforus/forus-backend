<?php

namespace App\Exports\BIExporters;

use App\Exports\FundProvidersExport;
use App\Models\FundProvider;
use App\Searches\FundProviderSearch;
use App\Services\BIConnectionService\Exporters\BaseBIExporter;

class BIFundProvidersExporter extends BaseBIExporter
{
    protected string $key = 'fund_providers';
    protected string $name = 'Aanbieders';

    /**
     * @return array
     */
    public function toArray(): array
    {
        $search = new FundProviderSearch([], FundProvider::query(), $this->organization);
        $data = new FundProvidersExport($search->query(), FundProvidersExport::getExportFieldsRaw());

        return $data->collection()->toArray();
    }
}
