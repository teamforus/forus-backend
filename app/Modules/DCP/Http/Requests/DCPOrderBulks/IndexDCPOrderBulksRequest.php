<?php

namespace App\Modules\DCP\Http\Requests\DCPOrderBulks;

use App\Http\Requests\BaseFormRequest;
use App\Modules\DCP\Models\DCPOrderBulk;
use Illuminate\Support\Facades\Gate;

class IndexDCPOrderBulksRequest extends BaseFormRequest
{
    /**
     * @return bool
     */
    public function authorize(): bool
    {
        return Gate::allows('viewAny', [DCPOrderBulk::class, $this->getRouteOrganization()]);
    }

    /**
     * @return array
     */
    public function rules(): array
    {
        return [
            'q' => $this->qRule(),
            'page' => $this->pageRule(),
            'per_page' => $this->perPageRule(),
        ];
    }
}
