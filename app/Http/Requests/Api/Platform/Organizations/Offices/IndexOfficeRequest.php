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
     * @return true
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return string[]
     *
     * @psalm-return array{q: 'nullable|string', per_page: 'nullable|numeric|between:0,100'}
     */
    public function rules(): array
    {
        return [
            'q' => 'nullable|string',
            'per_page' => 'nullable|numeric|between:0,100',
        ];
    }
}
