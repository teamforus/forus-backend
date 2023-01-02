<?php

namespace App\Http\Requests\Api\Platform\Organizations\Vouchers;

use App\Http\Requests\BaseFormRequest;
use App\Models\Organization;
use App\Models\Voucher;
use Illuminate\Support\Facades\Gate;

/**
 * Class UpdateVoucherRequest
 * @property-read Organization $organization
 * @property-read Voucher $voucher
 * @package App\Http\Requests\Api\Platform\Organizations\Vouchers
 */
class UpdateVoucherRequest extends BaseFormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize(): bool
    {
        return Gate::allows('update', [$this->voucher, $this->organization]);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules(): array
    {
        return [
            'limit_multiplier' => 'nullable|numeric|min:1|not_in:'. $this->voucher->limit_multiplier,
        ];
    }
}
