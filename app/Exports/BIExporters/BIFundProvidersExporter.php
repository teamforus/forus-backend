<?php

namespace App\Exports\BIExporters;

use App\Exports\FundProvidersExport;
use App\Http\Requests\Api\Platform\Organizations\Sponsor\Providers\IndexProvidersRequest;
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
        $request = new IndexProvidersRequest();
        $data = new FundProvidersExport($request, $this->organization, FundProvidersExport::getExportFieldsRaw());

        return $data->collection()->toArray();
    }
}
