<?php

namespace App\Http\Requests\Api\Platform\Funds\Requests;

use App\Http\Requests\BaseFormRequest;

/**
 * Class AssignEmployeeFundRequestRequest
 * @package App\Http\Requests\Api\Platform\Funds\Requests
 */
class AssignEmployeeFundRequestRequest extends BaseFormRequest
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
            'employee' => 'required|exists:employees,identity_address'
        ];
    }
}
