<?php

namespace App\Exports\BIExporters;

use App\Exports\VoucherExport;
use App\Http\Requests\BaseFormRequest;
use App\Models\Voucher;
use App\Scopes\Builders\VoucherSubQuery;
use App\Services\BIConnectionService\Exporters\BaseBIExporter;
use Exception;

class BIVouchersExporter extends BaseBIExporter
{
    protected string $key = 'vouchers';
    protected string $name = 'Tegoeden';

    /**
     * @throws Exception
     * @return array
     */
    public function toArray(): array
    {
        $formRequest = new BaseFormRequest(['type' => 'all', 'source' => 'all']);
        $fields = VoucherExport::getExportFieldsRaw();

        $query = Voucher::searchSponsorQuery($formRequest, $this->organization);
        $query = VoucherSubQuery::appendFirstUseFields($query);

        $vouchers = $query->with([
            'identity:id,address',
            'identity.primary_email:email',
            'identity.record_bsn:value',
            'transactions:voucher_id,amount',
            'reimbursements_pending:voucher_id,amount',
            'top_up_transactions:voucher_id,amount',
            'voucher_relation:voucher_id,bsn',
            'fund:id,organization_id,name',
            'fund.organization:id,bsn_enabled',
            'fund.fund_config:fund_id,implementation_id,allow_voucher_records',
            'fund.fund_config.implementation:name',
            'product:name',
            'paid_out_transactions:voucher_id',
            'product_vouchers:id,parent_id,amount',
            'product_vouchers.paid_out_transactions:voucher_id',
            'voucher_records:id,voucher_id,record_type_id,value',
            'voucher_records.record_type:id,key',
        ])->get();

        $array = Voucher::exportOnlyDataArray($vouchers, $fields);
        $fieldLabels = array_pluck(VoucherExport::getExportFields('product'), 'name', 'key');

        return $this->transformKeys($array, $fieldLabels);
    }
}
