<?php

namespace App\Http\Requests\Api\Platform\Funds\Requests;

use App\Http\Requests\BaseFormRequest;
use App\Models\FundRequest;
use App\Models\Organization;

class IndexFundRequestsRequest extends BaseFormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
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
        return [
            'state' => 'nullable|in:' . implode(',', FundRequest::STATES),
        ];
    }
}
