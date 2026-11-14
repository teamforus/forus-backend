<?php

namespace App\Modules\DCP\Http\Requests\DCPOrderBulks;

use App\Http\Requests\BaseFormRequest;
use App\Modules\DCP\Models\DCPOrderBulk;
use Illuminate\Support\Facades\Gate;

class StoreDCPOrderBulkRequest extends BaseFormRequest
{
    /**
     * @return bool
     */
    public function authorize(): bool
    {
        return Gate::allows('store', [DCPOrderBulk::class, $this->getRouteOrganization()]);
    }
}
