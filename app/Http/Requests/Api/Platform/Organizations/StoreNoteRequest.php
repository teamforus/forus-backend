<?php

namespace App\Http\Requests\Api\Platform\Organizations;

use App\Http\Requests\BaseFormRequest;

abstract class StoreNoteRequest extends BaseFormRequest
{


    /**
     * Get the validation rules that apply to the request.
     *
     * @return string[]
     *
     * @psalm-return array{description: 'required|string|min:5,max:2000'}
     */
    public function rules(): array
    {
        return [
            'description' => 'required|string|min:5,max:2000',
        ];
    }
}
