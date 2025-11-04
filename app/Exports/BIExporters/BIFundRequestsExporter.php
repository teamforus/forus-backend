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
        $employee = $this->organization->findEmployee($this->organization->identity_address);
        $search = (new FundRequestSearch([], FundRequest::query()))->setEmployee($employee);
        $export = new FundRequestsExport($search->query(), FundRequestsExport::getExportFieldsRaw());

        return $export->collection()->toArray();
    }
}
