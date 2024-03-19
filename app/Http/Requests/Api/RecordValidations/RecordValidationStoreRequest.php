<?php

namespace App\Http\Requests\Api\RecordValidations;

use App\Http\Requests\BaseFormRequest;

class RecordValidationStoreRequest extends BaseFormRequest
{


    /**
     * Get the validation rules that apply to the request.
     *
     * @return string[]
     *
     * @psalm-return array{record_id: 'required|numeric|exists:records,id'}
     */
    public function rules(): array
    {
        return [
            'record_id' => 'required|numeric|exists:records,id',
        ];
    }
}
