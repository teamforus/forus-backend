<?php

namespace App\Http\Requests\Api\Platform\Organizations\Sponsor\EventLog;

use App\Http\Requests\BaseFormRequest;

class IndexEventLogRequest extends BaseFormRequest
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
            'q' => 'nullable|string|max:500',
            'loggable' => 'required_with:loggable_id|array' .
                ($this->request->has('loggable_id') ? '|size:1' : ''),
            'loggable.*' => 'required|string',
            'loggable_id' => 'nullable|integer',
            'per_page'  => $this->perPageRule(),
        ];
    }
}