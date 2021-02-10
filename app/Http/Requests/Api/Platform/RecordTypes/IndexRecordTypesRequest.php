<?php

namespace App\Http\Requests\Api\Platform\RecordTypes;

use App\Http\Requests\BaseFormRequest;

/**
 * Class IndexRecordTypesRequest
 * @package App\Http\Requests\Api\Platform\RecordTypes
 */
class IndexRecordTypesRequest extends BaseFormRequest
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
            'insertable_only' => 'nullable|boolean',
        ];
    }
}
