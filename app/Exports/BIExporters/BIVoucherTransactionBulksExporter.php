<?php

namespace App\Exports\BIExporters;

use App\Exports\VoucherTransactionBulksExport;
use App\Models\VoucherTransactionBulk;
use App\Searches\VoucherTransactionBulksSearch;
use App\Services\BIConnectionService\Exporters\BaseBIExporter;
use Illuminate\Database\Eloquent\Builder;

class BIVoucherTransactionBulksExporter extends BaseBIExporter
{
    protected string $key = 'voucher_transaction_bulks';
    protected string $name = 'Bulk transacties vanuit tegoeden';

    /**
     * @return array
     */
    public function toArray(): array
    {
        $fields = VoucherTransactionBulksExport::getExportFieldsRaw();

        $query = VoucherTransactionBulk::whereHas('bank_connection', fn (Builder $q) => $q->where([
            'bank_connections.organization_id' => $this->organization->id,
        ]));

        $search = new VoucherTransactionBulksSearch([], $query);
        $data = new VoucherTransactionBulksExport($search->query(), $fields);

        return $data->collection()->toArray();
    }
}
