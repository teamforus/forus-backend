<?php

namespace App\Http\Requests\Api\Records;

use App\Http\Requests\BaseFormRequest;
use App\Rules\RecordCategoryIdRule;
use Illuminate\Validation\Rule;

class SortRecordsRequest extends BaseFormRequest
{


    /**
     * Get the validation rules that apply to the request.
     *
     * @return ((\Illuminate\Validation\Rules\Exists|string)[]|string)[]
     *
     * @psalm-return array{records: 'nullable|array', 'records.*': list{'nullable', 'numeric', \Illuminate\Validation\Rules\Exists}}
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
