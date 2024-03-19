<?php

namespace App\Http\Requests\Api\Platform\Organizations\Vouchers;

use App\Http\Requests\BaseFormRequest;
use Illuminate\Support\Facades\Gate;
use App\Models\Organization;
use App\Models\Voucher;

/**
 * Class AssignVoucherRequest
 * @property-read Organization $organization
 * @property-read Voucher $voucher
 * @package App\Http\Requests\Api\Platform\Organizations\Vouchers
 */
class ActivateVoucherRequest extends BaseFormRequest
{


    /**
     * Get the validation rules that apply to the request.
     *
     * @return string[]
     *
     * @psalm-return array{note: 'nullable|string|min:2|max:140'}
     */
    public function rules(): array
    {
        return [
            'note' => 'nullable|string|min:2|max:140',
        ];
    }
}
