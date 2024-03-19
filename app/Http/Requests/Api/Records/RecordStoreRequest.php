<?php

namespace App\Http\Requests\Api\Records;

use App\Http\Requests\BaseFormRequest;
use App\Rules\RecordCategoryIdRule;
use App\Rules\RecordTypeKeyExistsRule;

/**
 * Class RecordStoreRequest
 * @package App\Http\Requests\Api\Records
 */
class RecordStoreRequest extends BaseFormRequest
{


    /**
     * Get the validation rules that apply to the request.
     *
     * @return ((RecordCategoryIdRule|RecordTypeKeyExistsRule|mixed|string)[]|string)[]
     *
     * @psalm-return array{type: list{'required', RecordTypeKeyExistsRule}, value: array{0: 'required'|mixed,...}, order: 'nullable|numeric|min:0', record_category_id: list{'nullable', RecordCategoryIdRule}}
     */
    public function rules(): array
    {
        $type = $this->input('type');

        return [
            'type' => [
                'required',
                new RecordTypeKeyExistsRule(false),
            ],
            'value' => [
                'required',
                ...$type === 'email' || $type === 'primary_email' ? $this->emailRules() : []
            ],
            'order' => 'nullable|numeric|min:0',
            'record_category_id' => ['nullable', new RecordCategoryIdRule()]
        ];
    }
}
