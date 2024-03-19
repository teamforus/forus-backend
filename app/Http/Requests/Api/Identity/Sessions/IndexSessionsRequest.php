<?php

namespace App\Http\Requests\Api\Identity\Sessions;

use App\Http\Requests\BaseFormRequest;

class IndexSessionsRequest extends BaseFormRequest
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
