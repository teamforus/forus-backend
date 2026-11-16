<?php

namespace App\Modules\VoucherPass\Http\Requests\VoucherPasses;

use App\Http\Requests\BaseFormRequest;
use App\Modules\VoucherPass\Models\VoucherPass;
use App\Modules\VoucherPass\Rules\VoucherPassRowsRule;
use App\Modules\VoucherPass\Services\VoucherPassService;
use Illuminate\Support\Facades\Gate;

class StoreBatchVoucherPassesRequest extends BaseFormRequest
{
    /**
     * @return bool
     */
    public function authorize(): bool
    {
        return Gate::allows('create', [VoucherPass::class, $this->getRouteOrganization()]);
    }

    /**
     * @return array
     */
    public function rules(): array
    {
        return [
            'rows' => [
                'bail', 'required', 'array', 'list', 'min:1', 'max:' . VoucherPassService::BATCH_SIZE,
                new VoucherPassRowsRule(),
            ],
            'rows.*' => ['required', 'array'],
        ];
    }
}
