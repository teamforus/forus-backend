<?php

namespace App\Exports\BIExporters;

use App\Exports\FundRequestsExport;
use App\Http\Requests\Api\Platform\Funds\Requests\IndexFundRequestsRequest;
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
        $request = new IndexFundRequestsRequest();
        $fields = FundRequestsExport::getExportFieldsRaw();
        $employee = $this->organization->findEmployee($this->organization->identity_address);
        $data = new FundRequestsExport($request, $employee, $fields);

        return $data->collection()->toArray();
    }
}
