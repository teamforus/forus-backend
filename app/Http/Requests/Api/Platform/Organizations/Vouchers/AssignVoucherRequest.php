<?php

namespace App\Http\Requests\Api\Platform\Organizations\Vouchers;

use App\Http\Requests\BaseFormRequest;
use App\Models\Organization;
use App\Models\Voucher;
use App\Rules\BsnRule;

/**
 * @property-read Organization $organization
 * @property-read Voucher $voucher
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
        return
            $this->organization->identityCan($this->identity(), 'manage_vouchers') &&
            $this->voucher->fund->organization_id === $this->organization->id &&
            !$this->voucher->granted;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules(): array
    {
        return $this->organization->bsn_enabled ? [
            'email' => [
                'required_without:bsn',
                ...$this->emailRules(),
            ],
            'bsn' => ['required_without:email', new BsnRule()],
        ] : [
            'email' => [
                'required',
                ...$this->emailRules(),
            ],
            'bsn' => 'nullable|in:',
        ];
    }
}
