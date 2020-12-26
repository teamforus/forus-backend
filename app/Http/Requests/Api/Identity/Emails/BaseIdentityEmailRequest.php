<?php

namespace App\Http\Requests\Api\Identity\Emails;

use App\Http\Requests\BaseFormRequest;

class BaseIdentityEmailRequest extends BaseFormRequest
{
    protected $maxAttempts = 5;
    protected $decayMinutes = 30;

    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize(): bool
    {
        return $this->isAuthenticated();
    }
}
