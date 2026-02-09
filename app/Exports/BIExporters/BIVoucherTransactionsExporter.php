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
        $search = new VoucherTransactionsSearch([], VoucherTransaction::query());

        $export = new VoucherTransactionsSponsorExport(
            $search->searchSponsor($this->organization),
            VoucherTransactionsSponsorExport::getExportFieldsRaw(),
        );

        return $export->collection()->toArray();
    }
}
