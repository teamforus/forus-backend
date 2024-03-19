<?php

namespace App\Http\Requests\Api\Records;

use App\Http\Requests\BaseFormRequest;
use App\Rules\RecordCategoryIdRule;

class RecordUpdateRequest extends BaseFormRequest
{


    /**
     * Get the validation rules that apply to the request.
     *
     * @return ((RecordCategoryIdRule|string)[]|string)[]
     *
     * @psalm-return array{order: 'nullable|numeric|min:0', record_category_id: list{'nullable', RecordCategoryIdRule}}
     */
    public function rules(): array
    {
        return [
            'order' => 'nullable|numeric|min:0',
            'record_category_id' => ['nullable', new RecordCategoryIdRule()],
        ];
    }
}
