<?php

namespace App\Http\Requests\Api\Platform\BankConnections;

use App\Http\Requests\BaseFormRequest;

class RedirectBankConnectionRequest extends BaseFormRequest
{
    protected int $maxAttempts = 5;
    protected int $decayMinutes = 30;

    /**
     * Determine if the user is authorized to make this request.
     *
     * @throws \App\Exceptions\AuthorizationJsonException
     * @return bool
     */
    public function authorize(): bool
    {
        $this->throttleWithKey('to_many_attempts', $this, 'bank_connection_redirect');

        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules(): array
    {
        return [];
    }
}
