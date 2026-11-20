<?php

namespace App\Http\Requests\Api\Platform\Organizations\Vouchers;

use App\Http\Requests\BaseFormRequest;
use App\Modules\Voucher\Services\ProfileVoucherAwardService;
use Illuminate\Validation\Rule;

class VouchersFromProfilesRequest extends BaseFormRequest
{
    /**
     * @return array
     */
    public function rules(): array
    {
        return [
            'profile_ids' => ['sometimes', 'array', 'list', 'max:' . ProfileVoucherAwardService::BATCH_SIZE],
            'profile_ids.*' => ['required', 'integer', 'distinct'],
            'fund_ids' => 'required|array|min:1',
            'fund_ids.*' => [
                'required',
                'integer',
                'distinct',
                Rule::exists('funds', 'id')->where('organization_id', $this->getRouteOrganization()->id),
            ],
        ];
    }
}
