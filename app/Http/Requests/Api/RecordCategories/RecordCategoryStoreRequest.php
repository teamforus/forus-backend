<?php

namespace App\Http\Requests\Api\RecordCategories;

use App\Http\Requests\BaseFormRequest;

/**
 * Class RecordCategoryStoreRequest
 * @package App\Http\Requests\Api\RecordCategories
 */
class RecordCategoryStoreRequest extends BaseFormRequest
{


    /**
     * Get the validation rules that apply to the request.
     *
     * @return string[]
     *
     * @psalm-return array{name: 'required|between:2,16', order: 'nullable|numeric|min:0'}
     */
    public function rules(): array
    {
        return [
            'name'  => 'required|between:2,16',
            'order' => 'nullable|numeric|min:0',
        ];
    }
}
