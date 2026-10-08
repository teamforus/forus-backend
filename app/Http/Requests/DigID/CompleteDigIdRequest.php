<?php

namespace App\Http\Requests\DigID;

use App\Http\Requests\BaseFormRequest;

class CompleteDigIdRequest extends BaseFormRequest
{
    /**
     * @return bool
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array
     */
    public function rules(): array
    {
        return [
            'session_uid' => 'required|string|max:200',
            'completion_code' => ['required', 'string', 'regex:/\A[a-f0-9]{64}\z/'],
            'browser_verifier' => ['required', 'string', 'regex:/\A[a-f0-9]{64}\z/'],
        ];
    }
}
