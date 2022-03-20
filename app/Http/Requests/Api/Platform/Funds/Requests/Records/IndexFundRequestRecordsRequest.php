<?php

namespace App\Http\Requests\Api\Platform\Funds\Requests\Records;

use Illuminate\Foundation\Http\FormRequest;

class IndexFundRequestRecordsRequest extends FormRequest
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
            'per_page' => 'nullable|int|between:1,100',
        ];
    }
}
