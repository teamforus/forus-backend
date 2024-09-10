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
                ...$this->emailRules(),
            ],
            'phone' => 'nullable|string',
            'message' => 'nullable|string',
            'organization_name' => 'nullable|string',
            'accept_privacy_terms' => 'required|boolean|accepted',
            'accept_product_update_terms' => 'nullable|boolean',
        ];
    }

    /**
     * @return string[]
     */
    public function messages(): array
    {
        return [
            'name.required' => 'Deze informatie hebben we nodig om uw aanvraag goed te verwerken.',
            'email.required' => 'Deze informatie hebben we nodig om uw aanvraag goed te verwerken.',
            'accept_privacy_terms.accepted' => 'Om verder te gaan, dient u toestemming geven voor '.
                'het verwerken en opslaan van uw persoonsgegevens.',
        ];
    }
}