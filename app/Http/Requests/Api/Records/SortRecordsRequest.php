<?php

namespace App\Http\Requests\Api\Records;

use App\Http\Requests\BaseFormRequest;
use App\Rules\RecordCategoryIdRule;
use Illuminate\Validation\Rule;

class SortRecordsRequest extends BaseFormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize(): bool
    {
        return $this->isAuthenticated();
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules(): array
    {
        return [
            'records' => 'nullable|array',
            'records.*' => [
                'nullable',
                'numeric',
                Rule::exists('records', 'id')->where('identity_address', $this->auth_address()),
            ],
        ];
    }
}
