<?php

namespace App\Exports\BIExporters;

use App\Exports\VoucherTransactionBulksExport;
use App\Http\Requests\BaseFormRequest;
use App\Services\BIConnectionService\Exporters\BaseBIExporter;

class BIVoucherTransactionBulksExporter extends BaseBIExporter
{
    protected string $key = 'voucher_transaction_bulks';

    /**
     * @return array
     */
    public function toArray(): array
    {
        $formRequest = new BaseFormRequest();
        $fields = array_keys(VoucherTransactionBulksExport::getExportFieldsRaw());
        $data = new VoucherTransactionBulksExport($formRequest, $this->organization, $fields);

        return $data->collection()->toArray();
    }
}