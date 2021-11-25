<?php

namespace App\Http\Requests\Api\Platform\BankConnections;

use App\Http\Requests\BaseFormRequest;

class RedirectBankConnectionRequest extends BaseFormRequest
{
    protected $maxAttempts = 5;
    protected $decayMinutes = 30;

    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     * @throws \App\Exceptions\AuthorizationJsonException
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
