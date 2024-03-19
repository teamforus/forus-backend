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
     * @return string[]
     *
     * @psalm-return array{client_uid: 'nullable|string|max:20'}
     */
    public function rules(): array
    {
        return [
            'client_uid' => 'nullable|string|max:20',
        ];
    }
}
