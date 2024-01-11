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
     * @return bool
     * @throws AuthorizationJsonException
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
            'phone' => 'required|string|max:191',
        ];
    }

    /**
     * @return void
     * @throws AuthorizationJsonException
     */
    protected function throttle(): void
    {
        $this->maxAttempts = Config::get('forus.throttles.mollie.create_profile.attempts');
        $this->decayMinutes = Config::get('forus.throttles.mollie.create_profile.decay');

        $this->throttleWithKey('to_many_attempts', $this, 'mollie_connection_profile');
    }
}
