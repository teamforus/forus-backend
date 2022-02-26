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
class AssignVoucherRequest extends BaseFormRequest
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
            !$this->voucher->is_granted;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules(): array
    {
        return $this->organization->bsn_enabled ? [
            'email' => 'required_without:bsn|email:strict',
            'bsn' => 'required_without:email|string|between:8,9',
        ] : [
            'email' => 'required|email:strict',
        ];
    }
}
