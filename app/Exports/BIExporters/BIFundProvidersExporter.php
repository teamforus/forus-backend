<?php

namespace App\Exports\BIExporters;

use App\Exports\FundProvidersExport;
use App\Http\Requests\BaseFormRequest;
use App\Services\BIConnectionService\Exporters\BaseBIExporter;

class BIFundProvidersExporter extends BaseBIExporter
{
    protected string $key = 'fund_providers';

    /**
     * @return array
     */
    public function toArray(): array
    {
        $formRequest = new BaseFormRequest();
        $data = new FundProvidersExport($formRequest, $this->organization);

        return $data->collection()->toArray();
    }
}