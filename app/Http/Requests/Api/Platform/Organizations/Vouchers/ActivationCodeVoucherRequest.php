<?php

namespace App\Http\Requests\Api\Platform\Organizations\Vouchers;

use App\Http\Requests\BaseFormRequest;
use App\Models\Organization;
use App\Models\Permission;
use App\Models\Voucher;

/**
 * @property-read Organization $organization
 * @property-read Voucher $voucher
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
        return
            $this->organization->identityCan($this->identity(), Permission::MANAGE_VOUCHERS) &&
            $this->voucher->fund->organization_id === $this->organization->id &&
            !$this->voucher->granted &&
            !$this->voucher->expired &&
            !$this->voucher->activation_code;
    }

    /**
     * @return string[]
     */
    public function rules(): array
    {
        return [
            'client_uid' => 'nullable|string|max:20',
        ];
    }
}
