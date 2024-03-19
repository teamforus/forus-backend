<?php

namespace App\Http\Requests\Api\Platform\Organizations\Sponsor\Transactions;

use App\Exports\VoucherTransactionsSponsorExport;
use App\Http\Requests\Api\Platform\Organizations\Transactions\BaseIndexTransactionsRequest;
use Illuminate\Validation\Rule;
use Illuminate\Support\Arr;

class IndexTransactionsRequest extends BaseIndexTransactionsRequest
{
    /**
     * @return ((\Illuminate\Validation\Rules\In|string)[]|mixed|string)[]
     *
     * @psalm-return array{fields: 'nullable|array', 'fields.*': list{'nullable', \Illuminate\Validation\Rules\In},...}
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