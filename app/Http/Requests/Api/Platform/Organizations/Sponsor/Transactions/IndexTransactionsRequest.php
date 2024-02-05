<?php

namespace App\Http\Requests\Api\Platform\Organizations\Sponsor\Transactions;

use App\Exports\VoucherTransactionsSponsorExport;
use App\Http\Requests\Api\Platform\Organizations\Transactions\BaseIndexTransactionsRequest;
use Illuminate\Validation\Rule;
use Illuminate\Support\Arr;

class IndexTransactionsRequest extends BaseIndexTransactionsRequest
{
    /**
     * @return array
     */
    public function rules(): array
    {
        $fields = Arr::pluck(VoucherTransactionsSponsorExport::getExportFields(), 'key');

        return array_merge(parent::rules(), [
            'fields' => 'nullable|array',
            'fields.*' => ['nullable', Rule::in($fields)],
        ]);
    }
}