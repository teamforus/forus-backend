<?php

namespace App\Modules\VoucherPass\Http\Requests\VoucherPasses;

use App\Http\Requests\BaseFormRequest;
use App\Modules\VoucherPass\Models\VoucherPass;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class IndexVoucherPassesRequest extends BaseFormRequest
{
    /**
     * @return bool
     */
    public function authorize(): bool
    {
        return Gate::allows('viewAny', [VoucherPass::class, $this->getRouteOrganization()]);
    }

    /**
     * @return array
     */
    public function rules(): array
    {
        return [
            'q' => $this->qRule(),
            'state' => ['nullable', Rule::in([VoucherPass::STATE_ACTIVE, VoucherPass::STATE_DEACTIVATED])],
            'print_requested' => 'nullable|boolean',
            'page' => $this->pageRule(),
            'per_page' => $this->perPageRule(),
        ];
    }
}
