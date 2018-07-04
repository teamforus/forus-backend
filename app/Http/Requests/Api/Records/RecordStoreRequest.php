<?php

namespace App\Http\Requests\Api\Records;

use App\Rules\IdentityRecordsUniqueRule;
use App\Rules\RecordCategoryIdRule;
use App\Rules\RecordTypeKeyExistsRule;
use Illuminate\Foundation\Http\FormRequest;

class RecordStoreRequest extends FormRequest
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
        $type = request()->get('type');
        $valueRules = ['required'];

        if ($type == 'email' || $type == 'primary_email') {
            array_push($valueRules, 'email');
        }

        if ($type == 'primary_email') {
            array_push($valueRules, new IdentityRecordsUniqueRule('primary_email'));
        }

        return [
            'type' => ['required', new RecordTypeKeyExistsRule()],
            'value' => $valueRules,
            'order' => 'nullable|numeric|min:0',
            'record_category_id' => ['nullable', new RecordCategoryIdRule()]
        ];
    }
}
