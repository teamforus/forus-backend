<?php

namespace App\Http\Requests\Api\Platform\Vouchers;

use App\Models\Voucher;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;

/**
 * @property-read Voucher $voucher_number_or_address
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
        return Gate::allows('deactivateRequester', $this->voucher_number_or_address);
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
