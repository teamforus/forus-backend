<?php

namespace App\Exports\BIExporters;

use App\Exports\ReimbursementsSponsorExport;
use App\Models\Reimbursement;
use App\Searches\ReimbursementsSearch;
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
        $fields = ReimbursementsSponsorExport::getExportFieldsRaw();

        $query = Reimbursement::where('state', '!=', Reimbursement::STATE_DRAFT);
        $query = $query->whereRelation('voucher.fund', 'organization_id', $this->organization->id);
        $search = new ReimbursementsSearch([], $query);

        $data = new ReimbursementsSponsorExport($search->query()->latest(), $fields);

        return $data->collection()->toArray();
    }
}
