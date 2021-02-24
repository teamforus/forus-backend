<?php

namespace App\Http\Requests\Api;

use App\Http\Requests\BaseFormRequest;
use App\Models\Implementation;
use App\Rules\IdentityEmailExistsRule;

/**
 * Class IdentityAuthorizationEmailTokenRequest
 * @package App\Http\Requests\Api
 */
class IdentityAuthorizationEmailTokenRequest extends BaseFormRequest
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
     * @throws \App\Exceptions\AuthorizationJsonException
     */
    public function rules(): array
    {
        $this->maxAttempts = env('AUTH_THROTTLE_ATTEMPTS', 10);
        $this->decayMinutes = env('AUTH_THROTTLE_DECAY', 10);
        $this->throttleWithKey('to_many_attempts', $this, 'auth');

        // TODO: remove `primary_email` when iOS is ready
        $emailKey = $this->has('email') ? 'email': 'primary_email';

        return [
            $emailKey => [
                'required',
                'email:strict',
                new IdentityEmailExistsRule()
            ],
            'source' => 'required|in:' . Implementation::keysAvailable()->implode(','),
            'target' => 'nullable|alpha_dash'
        ];
    }
}
