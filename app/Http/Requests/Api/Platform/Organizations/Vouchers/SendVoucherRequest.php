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
     * Get the validation rules that apply to the request.
     *
     * @return (IdentityEmailExistsRule|mixed|string)[][]
     *
     * @psalm-return array{email: array{0: 'required'|mixed, 1: IdentityEmailExistsRule|mixed,...}}
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
