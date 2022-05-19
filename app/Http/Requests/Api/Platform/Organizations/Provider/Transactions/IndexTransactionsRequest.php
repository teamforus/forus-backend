<?php

namespace App\Http\Requests\Api\Platform\Organizations\Provider\Transactions;

use App\Exports\VoucherTransactionsProviderFieldedExport;
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
        $fields = Arr::pluck(VoucherTransactionsProviderFieldedExport::getExportFields(), 'key');

        return array_merge(parent::rules(), [
            'fields' => 'nullable|array',
            'fields.*' => ['nullable', Rule::in($fields)],
        ]);
    }
}