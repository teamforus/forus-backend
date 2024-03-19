<?php

namespace App\Http\Requests;

class BaseIndexFormRequest extends BaseFormRequest
{
    /**
     * @return string[]
     *
     * @psalm-return array{per_page: string}
     */
    public function rules(): array
    {
        return [
            'per_page' => $this->perPageRule(),
        ];
    }
}
