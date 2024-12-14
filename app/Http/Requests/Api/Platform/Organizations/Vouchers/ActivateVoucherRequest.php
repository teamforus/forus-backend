<?php

namespace App\Http\Requests\Api\Platform\Organizations\Vouchers;

use App\Http\Requests\BaseFormRequest;
use Illuminate\Support\Facades\Gate;
use App\Models\Organization;
use App\Models\Voucher;

/**
 * @property-read Organization $organization
 * @property-read Voucher $voucher
 */
class ActivateVoucherRequest extends BaseFormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize(): bool
    {
        return Gate::allows('activateSponsor', [$this->voucher, $this->organization]);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules(): array
    {
        return [
            'note' => 'nullable|string|min:2|max:140',
        ];
    }
}
