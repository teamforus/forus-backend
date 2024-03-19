<?php

namespace App\Http\Requests\Api\Platform\Organizations\Vouchers;

use App\Http\Requests\BaseFormRequest;
use App\Models\Organization;

/**
 * Class IndexVouchersRequest
 * @property-read Organization $organization
 * @package App\Http\Requests\Api\Platform\Organizations\Vouchers
 */
class IndexVouchersExportFieldsRequest extends BaseFormRequest
{


    /**
     * Get the validation rules that apply to the request.
     *
     * @return string[]
     *
     * @psalm-return array{type: 'nullable|in:budget,product'}
     */
    public function rules(): array
    {
        return [
            'type' => 'nullable|in:budget,product',
        ];
    }
}
