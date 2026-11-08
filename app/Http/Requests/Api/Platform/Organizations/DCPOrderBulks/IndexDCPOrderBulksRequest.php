<?php

namespace App\Http\Requests\Api\Platform\Organizations\DCPOrderBulks;

use App\Http\Requests\BaseFormRequest;
use App\Services\DCPService\Models\DCPOrderBulk;
use Illuminate\Validation\Rule;

class IndexDCPOrderBulksRequest extends BaseFormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules(): array
    {
        return [
            'state' => ['nullable', Rule::in(DCPOrderBulk::STATES)],
            'per_page' => $this->perPageRule(),
        ];
    }
}
