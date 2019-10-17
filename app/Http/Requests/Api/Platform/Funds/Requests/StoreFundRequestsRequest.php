<?php

namespace App\Http\Requests\Api\Platform\Funds\Requests;

use App\Rules\RecordTypeKeyExistsRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreFundRequestsRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'records' => [
                'present',
                'array',
                'min:1',
            ],
            'records.*.value' => [
                'required'
            ],
            'records.*.record_type_key' => [
                'required',
                'not_in:primary_email',
                new RecordTypeKeyExistsRule()
            ],
            'records.*.files' => [
                'nullable', 'array'
            ],
            'records.*.files.*' => [
                'required',
                'exists:files,uid',
            ],
        ];
    }
}
