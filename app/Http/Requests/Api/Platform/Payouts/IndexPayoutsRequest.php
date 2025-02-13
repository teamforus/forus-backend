<?php

namespace App\Http\Requests\Api\Platform\Payouts;

use App\Http\Requests\BaseFormRequest;

class IndexPayoutsRequest extends BaseFormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules(): array
    {
        return $this->sortableResourceRules();
    }
}
