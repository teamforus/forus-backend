<?php

namespace App\Modules\VoucherPass\Http\Requests\VoucherPassDCP;

use App\Http\Requests\BaseFormRequest;
use App\Modules\DCP\Models\DCPOrder;
use App\Modules\VoucherPass\Services\VoucherPassDCPService;
use Illuminate\Support\Facades\Gate;

class PrepareVoucherPassDCPRequest extends BaseFormRequest
{
    /**
     * @return bool
     */
    public function authorize(): bool
    {
        return Gate::allows('create', [DCPOrder::class, $this->getRouteOrganization()]);
    }

    /**
     * @return array
     */
    public function rules(): array
    {
        return [
            'pass_ids' => ['present', 'array', 'list', 'max:' . VoucherPassDCPService::BATCH_SIZE],
            'pass_ids.*' => ['required', 'integer', 'min:1', 'distinct'],
        ];
    }
}
