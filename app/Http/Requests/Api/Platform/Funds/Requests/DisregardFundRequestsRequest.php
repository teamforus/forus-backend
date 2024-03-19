<?php

namespace App\Http\Requests\Api\Platform\Funds\Requests;

use App\Http\Requests\BaseFormRequest;
use App\Models\FundRequest;

/**
 * Class DisregardFundRequestsRequest
 * @property FundRequest $fund_request
 * @package App\Http\Requests\Api\Platform\Funds\FundRequests
 */
class DisregardFundRequestsRequest extends BaseFormRequest
{


    /**
     * Get the validation rules that apply to the request.
     *
     * @return string[]
     *
     * @psalm-return array{note: 'nullable|string|between:0,2000', notify: 'required|boolean'}
     */
    public function rules(): array
    {
        return [
            'note'      => 'nullable|string|between:0,2000',
            'notify'    => 'required|boolean',
        ];
    }
}
