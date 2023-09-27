<?php

namespace App\Http\Requests\Api\Platform\Organizations\Vouchers;

use App\Http\Requests\BaseFormRequest;
use App\Rules\VoucherRecordsRule;

class StoreVoucherRecordsRequest extends BaseFormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules(): array
    {
        return [
            'vouchers.*.records' => $this->recordsRule(),
        ];
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
