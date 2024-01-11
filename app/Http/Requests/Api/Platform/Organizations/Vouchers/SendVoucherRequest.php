<?php

namespace App\Http\Requests\Api\Platform\Organizations\Vouchers;

use App\Http\Requests\BaseFormRequest;
use App\Models\Organization;
use App\Models\Voucher;
use App\Rules\IdentityEmailExistsRule;

/**
 * @property-read Organization $organization
 * @property-read Voucher $voucher
 */
class SendVoucherRequest extends BaseFormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize(): bool
    {
        return
            $this->organization->identityCan($this->identity(), 'manage_vouchers') &&
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
        return [
            'email' => [
                'required',
                new IdentityEmailExistsRule(),
                ...$this->emailRules(),
            ],
        ];
    }
}
