<?php

namespace App\Http\Requests\Api\Platform\Organizations\Vouchers;

use App\Http\Requests\BaseFormRequest;
use App\Models\Organization;
use App\Models\Voucher;
use App\Rules\BsnRule;

/**
 * Class AssignVoucherRequest
 * @property-read Organization $organization
 * @property-read Voucher $voucher
 * @package App\Http\Requests\Api\Platform\Organizations\Vouchers
 */
class AssignVoucherRequest extends BaseFormRequest
{


    /**
     * Get the validation rules that apply to the request.
     *
     * @return ((BsnRule|mixed|string)[]|string)[]
     *
     * @psalm-return array{email: array{0: 'required'|'required_without:bsn'|mixed,...}, bsn: 'nullable|in:'|list{'required_without:email', BsnRule}}
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
