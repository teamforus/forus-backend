<?php

namespace App\Modules\VoucherPass\Http\Requests\VoucherPasses;

use App\Http\Requests\BaseFormRequest;
use App\Modules\VoucherPass\Rules\VoucherPassRowsRule;
use Illuminate\Support\Facades\Gate;

class UpdateVoucherPassRequest extends BaseFormRequest
{
    /**
     * @return bool
     */
    public function authorize(): bool
    {
        return Gate::allows('update', [$this->route('voucherPass'), $this->getRouteOrganization()]);
    }

    /**
     * @return array
     */
    public function rules(): array
    {
        return VoucherPassRowsRule::rules();
    }
}
