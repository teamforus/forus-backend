<?php

namespace App\Exports\BIExporters;

use App\Exports\ReimbursementsSponsorExport;
use App\Http\Requests\Api\Platform\Organizations\Reimbursements\IndexReimbursementsRequest;
use App\Services\BIConnectionService\Exporters\BaseBIExporter;

class BIReimbursementsExporter extends BaseBIExporter
{
    protected string $key = 'reimbursements';
    protected string $name = 'Declaraties';

    /**
     * @return array
     */
    public function toArray(): array
    {
        $request = new IndexReimbursementsRequest();
        $fields = ReimbursementsSponsorExport::getExportFieldsRaw();
        $data = new ReimbursementsSponsorExport($request, $this->organization, $fields);

        return $data->collection()->toArray();
    }
}
