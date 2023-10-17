<?php

namespace App\Exports\BIExporters;

use App\Exports\VoucherExport;
use App\Http\Requests\BaseFormRequest;
use App\Models\Voucher;
use App\Scopes\Builders\VoucherSubQuery;
use App\Services\BIConnectionService\Exporters\BaseBIExporter;

class BIVouchersExporter extends BaseBIExporter
{
    /**
     * @return array
     * @throws \Exception
     */
    public function toArray(): array
    {
        $formRequest = new BaseFormRequest(['type' => 'all', 'source' => 'all']);
        $fields = VoucherExport::getExportFieldsRaw();

        $query = Voucher::searchSponsorQuery($formRequest, $this->organization);
        $query = VoucherSubQuery::appendFirstUseFields($query);

        $vouchers = $query->with([
            'transactions', 'voucher_relation', 'product', 'fund.fund_config',
            'token_without_confirmation', 'identity.primary_email', 'identity.record_bsn',
            'product_vouchers', 'top_up_transactions', 'reimbursements_pending',
            'voucher_records.record_type'
        ])->get();

        $array = Voucher::exportOnlyDataArray($vouchers, array_keys($fields));

        return $this->transformKeys($array, $fields);
    }
}