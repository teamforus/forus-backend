<?php

namespace App\Http\Requests\Api\Platform\Organizations\Vouchers;

use App\Http\Requests\BaseFormRequest;
use App\Models\Organization;
use App\Rules\VoucherRecordsRule;

/**
 * @property-read Organization $organization
 */
abstract class BaseStoreVouchersRequest extends BaseFormRequest
{


    /**
     * @return (VoucherRecordsRule|string)[]
     *
     * @psalm-return list{'nullable', 'array', VoucherRecordsRule}
     */
    protected function recordsRule(): array
    {
        return [
            'nullable',
            'array',
            new VoucherRecordsRule(),
        ];
    }
}
