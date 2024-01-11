<?php

namespace App\Http\Requests\Api\Contact;

use App\Exceptions\AuthorizationJsonException;
use App\Http\Requests\BaseFormRequest;
use App\Traits\ThrottleWithMeta;
use Illuminate\Support\Facades\Config;

class SendContactFormRequest extends BaseFormRequest
{
    use ThrottleWithMeta;

    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     * @throws AuthorizationJsonException
     */
    public function authorize(): bool
    {
        $this->maxAttempts = Config::get('forus.throttles.contact_form.attempts');
        $this->decayMinutes = Config::get('forus.throttles.contact_form.decay');

        $this->throttleWithKey('to_many_attempts', $this, 'contact_form');

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
            'name' => 'required|string',
            'email' => [
                'required',
                ...$this->emailRule(),
            ],
            'phone' => 'nullable|string',
            'organization' => 'nullable|string',
            'message' => 'required|string',
        ];
    }
}