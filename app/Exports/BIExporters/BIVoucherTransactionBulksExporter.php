<?php

namespace App\Exports\BIExporters;

use App\Exports\VoucherTransactionBulksExport;
use App\Http\Requests\Api\Platform\Organizations\Sponsor\TransactionBulks\IndexTransactionBulksRequest;
use App\Services\BIConnectionService\Exporters\BaseBIExporter;

class BIVoucherTransactionBulksExporter extends BaseBIExporter
{
    protected string $key = 'voucher_transaction_bulks';
    protected string $name = 'Bulk transacties vanuit tegoeden';

    /**
     * @return array
     */
    public function toArray(): array
    {
        $request = new IndexTransactionBulksRequest();
        $fields = VoucherTransactionBulksExport::getExportFieldsRaw();
        $data = new VoucherTransactionBulksExport($request, $this->organization, $fields);

        return $data->collection()->toArray();
    }
}
