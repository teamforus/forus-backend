<?php

namespace App\Exports\BIExporters;

use App\Exports\ReimbursementsSponsorExport;
use App\Http\Requests\BaseFormRequest;
use App\Services\BIConnectionService\Exporters\BaseBIExporter;

class BIReimbursementsExporter extends BaseBIExporter
{
    /**
     * @return array
     */
    public function toArray(): array
    {
        $formRequest = new BaseFormRequest();
        $fields = ReimbursementsSponsorExport::getExportFieldsRaw();
        $data = new ReimbursementsSponsorExport($formRequest, $this->organization, array_keys($fields));

        return $this->transformKeys($data->collection()->toArray(), $fields);
    }
}