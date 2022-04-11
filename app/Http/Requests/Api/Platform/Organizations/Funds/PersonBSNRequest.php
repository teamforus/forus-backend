<?php

namespace App\Http\Requests\Api\Platform\Organizations\Funds;

use App\Http\Requests\BaseFormRequest;

/**
 * Class PersonBSNRequest
 * @package App\Http\Requests\Api\Platform\Organizations\Funds
 */
class PersonBSNRequest extends BaseFormRequest
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
            'bsn' => 'required|string|digits:9'
        ];
    }
}
