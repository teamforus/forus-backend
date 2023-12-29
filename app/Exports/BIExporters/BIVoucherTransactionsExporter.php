<?php

namespace App\Exports\BIExporters;

use App\Exports\VoucherTransactionsSponsorExport;
use App\Http\Requests\BaseFormRequest;
use App\Services\BIConnectionService\Exporters\BaseBIExporter;

class BIVoucherTransactionsExporter extends BaseBIExporter
{
    protected string $key = 'voucher_transactions';

    /**
     * @return array
     */
    public function toArray(): array
    {
        $formRequest = new BaseFormRequest();
        $fields = array_keys(VoucherTransactionsSponsorExport::getExportFieldsRaw());
        $data = new VoucherTransactionsSponsorExport($formRequest, $this->organization, $fields);

        return $data->collection()->toArray();
    }
}