<?php

namespace App\Http\Requests\Api\Platform\Organizations\Vouchers;

use App\Http\Requests\BaseFormRequest;
use App\Models\Organization;
use App\Models\Voucher;
use Illuminate\Support\Facades\Gate;

/**
 * Class AssignVoucherRequest
 * @property-read Organization $organization
 * @property-read Voucher $voucher
 * @package App\Http\Requests\Api\Platform\Organizations\Vouchers
 */
class DeactivateVoucherRequest extends BaseFormRequest
{


    /**
     * @return string[]
     *
     * @psalm-return array{notify_by_email: 'nullable|bool', note: 'nullable|string|max:140'}
     */
    public function rules(): array
    {
        return [
            'notify_by_email'   => 'nullable|bool',
            'note'              => 'nullable|string|max:140',
        ];
    }
}
