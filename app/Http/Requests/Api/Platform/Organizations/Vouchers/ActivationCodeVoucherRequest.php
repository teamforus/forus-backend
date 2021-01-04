<?php

namespace App\Http\Requests\Api\Platform\Organizations\Vouchers;

use App\Http\Requests\BaseFormRequest;
use App\Models\Organization;
use App\Models\Voucher;

/**
 * Class AssignVoucherRequest
 * @property-read Organization $organization
 * @property-read Voucher $voucher
 * @package App\Http\Requests\Api\Platform\Organizations\Vouchers
 */
class ActivationCodeVoucherRequest extends BaseFormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize(): bool
    {
        return $this->organization->identityCan($this->auth_address(), 'manage_vouchers') &&
            $this->voucher->fund->organization_id === $this->organization->id &&
            !$this->voucher->is_granted &&
            !$this->voucher->expired &&
            !$this->voucher->activation_code;
    }

    /**
     * @return string[]
     */
    public function rules(): array
    {
        return [
            'activation_code_uid' => 'nullable|string|max:20',
        ];
    }
}
