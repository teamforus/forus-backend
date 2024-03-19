<?php

namespace App\Http\Requests\Api\Platform\Organizations\Sponsor\EventLog;

use App\Http\Requests\BaseFormRequest;

class IndexEventLogRequest extends BaseFormRequest
{


    /**
     * Get the validation rules that apply to the request.
     *
     * @return string[]
     *
     * @psalm-return array{q: 'nullable|string|max:500', loggable: 'required_with:loggable_id|array'|'required_with:loggable_id|array|size:1', 'loggable.*': 'required|string', loggable_id: 'nullable|integer', per_page: string}
     */
    public function rules(): array
    {
        return [
            'q' => 'nullable|string|max:500',
            'loggable' => 'required_with:loggable_id|array' .
                ($this->request->has('loggable_id') ? '|size:1' : ''),
            'loggable.*' => 'required|string',
            'loggable_id' => 'nullable|integer',
            'per_page'  => $this->perPageRule(),
        ];
    }
}