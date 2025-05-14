<?php

namespace App\Exports\BIExporters;

use App\Exports\ReimbursementsSponsorExport;
use App\Http\Requests\BaseFormRequest;
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
        $formRequest = new BaseFormRequest();
        $fields = ReimbursementsSponsorExport::getExportFieldsRaw();
        $data = new ReimbursementsSponsorExport($formRequest, $this->organization, $fields);

        return $data->collection()->toArray();
    }
}
