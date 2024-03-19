<?php

namespace App\Http\Requests\Api\Platform\BankConnections;

use App\Http\Requests\BaseFormRequest;

class RedirectBankConnectionRequest extends BaseFormRequest
{
    protected $maxAttempts = 5;
    protected $decayMinutes = 30;

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     *
     * @psalm-return array<never, never>
     */
    public function rules(): array
    {
        return [];
    }
}
