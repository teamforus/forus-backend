<?php

namespace App\Exports\BIExporters;

use App\Exports\VoucherTransactionsSponsorExport;
use App\Models\VoucherTransaction;
use App\Searches\VoucherTransactionsSearch;
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
        $fields = VoucherTransactionsSponsorExport::getExportFieldsRaw();
        $search = new VoucherTransactionsSearch([], VoucherTransaction::query());

        $data = new VoucherTransactionsSponsorExport(
            $search->searchSponsor($this->organization),
            $fields
        );

        return $data->collection()->toArray();
    }
}
