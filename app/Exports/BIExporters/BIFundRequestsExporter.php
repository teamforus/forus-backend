<?php

namespace App\Exports\BIExporters;

use App\Exports\FundRequestsExport;
use App\Http\Requests\BaseFormRequest;
use App\Services\BIConnectionService\Exporters\BaseBIExporter;

class BIFundRequestsExporter extends BaseBIExporter
{
    protected string $key = 'fund_requests';
    protected string $name = 'Aanvragen';

    /**
     * @return array
     */
    public function toArray(): array
    {
        $formRequest = new BaseFormRequest();
        $fields = FundRequestsExport::getExportFieldsRaw();
        $employee = $this->organization->findEmployee($this->organization->identity_address);
        $data = new FundRequestsExport($formRequest, $employee, $fields);

        return $data->collection()->toArray();
    }
}
