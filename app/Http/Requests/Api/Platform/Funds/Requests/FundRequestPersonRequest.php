<?php

namespace App\Http\Requests\Api\Platform\Funds\Requests;

use App\Http\Requests\BaseFormRequest;

/**
 * Class PersonBSNRequest
 * @package App\Http\Requests\Api\Platform\Organizations\Funds
 */
class FundRequestPersonRequest extends BaseFormRequest
{


    /**
     * Get the validation rules that apply to the request.
     *
     * @return string[]
     *
     * @psalm-return array{scope: 'nullable|in:parents,children,partners', scope_id: 'required_with:scope|integer'}
     */
    public function rules(): array
    {
        return [
            'scope' => 'nullable|in:parents,children,partners',
            'scope_id' => 'required_with:scope|integer',
        ];
    }
}
