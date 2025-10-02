<?php

namespace App\Exports\BIExporters;

use App\Exports\VoucherTransactionsSponsorExport;
use App\Http\Requests\Api\Platform\Organizations\Sponsor\Transactions\IndexTransactionsRequest;
use App\Services\BIConnectionService\Exporters\BaseBIExporter;

class BIVoucherTransactionsExporter extends BaseBIExporter
{
    protected string $key = 'voucher_transactions';
    protected string $name = 'Transacties vanuit tegoeden';

    /**
     * @return array
     */
    public function toArray(): array
    {
        $request = new IndexTransactionsRequest();
        $fields = VoucherTransactionsSponsorExport::getExportFieldsRaw();
        $data = new VoucherTransactionsSponsorExport($request, $this->organization, $fields);

        return $data->collection()->toArray();
    }
}
