<?php

namespace App\Exports\BIExporters;

use App\Exports\FundRequestsExport;
use App\Models\FundRequest;
use App\Searches\FundRequestSearch;
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
        $fields = FundRequestsExport::getExportFieldsRaw();
        $employee = $this->organization->findEmployee($this->organization->identity_address);
        $search = (new FundRequestSearch([], FundRequest::query()))->setEmployee($employee);
        $data = new FundRequestsExport($search->query(), $fields);

        return $data->collection()->toArray();
    }
}
