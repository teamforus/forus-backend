<?php

namespace App\Http\Requests\Api\Platform\Vouchers;

use App\Models\VoucherToken;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;

/**
 * Class ShareProductVoucherRequest
 * @property-read VoucherToken $voucher_token_address
 * @package App\Http\Requests\Api\Platform\Vouchers
 */
class DeactivateVoucherRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize(): bool
    {
        return Gate::allows('deactivateRequester', $this->voucher_token_address->voucher);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules(): array
    {
        return [
            'note' => 'required|string|min:2|max:140',
        ];
    }
}
