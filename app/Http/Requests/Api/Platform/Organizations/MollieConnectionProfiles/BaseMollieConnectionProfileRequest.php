<?php

namespace App\Http\Requests\Api\Platform\Organizations\MollieConnectionProfiles;

use App\Exceptions\AuthorizationJsonException;
use App\Http\Requests\BaseFormRequest;
use App\Models\Organization;
use Illuminate\Support\Facades\Config;

/**
 * @property-read Organization $organization
 */
abstract class BaseMollieConnectionProfileRequest extends BaseFormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @throws AuthorizationJsonException
     * @return bool
     */
    public function authorize(): bool
    {
        $this->throttle();

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
            'name' => 'required|string|max:191',
            'email' => [
                'required',
                'max:191',
                ...$this->emailRules(),
            ],
            'website' => 'required|url|max:191',
            'phone' => [
                'required',
                'string',
                'regex:/^\+[1-9]\d{10,14}$/',
            ],
        ];
    }

    /**
     * @throws AuthorizationJsonException
     * @return void
     */
    protected function throttle(): void
    {
        $this->maxAttempts = Config::get('forus.throttles.mollie.create_profile.attempts');
        $this->decayMinutes = Config::get('forus.throttles.mollie.create_profile.decay');

        $this->throttleWithKey('to_many_attempts', $this, 'mollie_connection_profile');
    }
}
