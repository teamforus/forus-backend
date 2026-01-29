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
            'q' => $this->qRule(),
            'page' => $this->pageRule(),
            'per_page' => $this->perPageRule(),
        ];
    }
}
