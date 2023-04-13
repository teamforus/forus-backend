<?php

namespace App\Http\Requests;

class BaseIndexFormRequest extends BaseFormRequest
{
    /**
     * @return array
     */
    public function rules(): array
    {
        return [
            'per_page' => $this->perPageRule(),
        ];
    }
}
