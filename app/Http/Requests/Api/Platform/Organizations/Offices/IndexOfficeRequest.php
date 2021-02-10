<?php

namespace App\Http\Requests\Api\Platform\Organizations\Offices;

/**
 * Class IndexOfficeRequest
 * @package App\Http\Requests\Api\Platform\Organizations\Offices
 */
class IndexOfficeRequest extends BaseOfficeRequest
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
            'per_page' => 'nullable|numeric|between:0,100',
        ];
    }
}
