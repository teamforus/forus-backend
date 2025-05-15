<?php

namespace App\Http\Requests\Api\Platform\Organizations\Provider\Transactions;

use App\Exports\VoucherTransactionsProviderExport;
use App\Http\Requests\Api\Platform\Organizations\Transactions\BaseIndexTransactionsRequest;

class IndexTransactionsRequest extends BaseIndexTransactionsRequest
{
    /**
     * @return array
     */
    public function rules(): array
    {
        return array_merge(parent::rules(), [
            ...$this->exportableResourceRules(VoucherTransactionsProviderExport::getExportFieldsRaw()),
        ]);
    }
}
