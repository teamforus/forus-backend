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
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize(): bool
    {
        return $this->organization->identityCan($this->identity(), 'manage_vouchers');
    }

    /**
     * @return array
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
