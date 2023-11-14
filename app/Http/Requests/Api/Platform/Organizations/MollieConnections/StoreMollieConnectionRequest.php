<?php

namespace App\Http\Requests\Api\Platform\Organizations\MollieConnections;

use App\Exceptions\AuthorizationJsonException;
use App\Http\Requests\BaseFormRequest;
use App\Models\Organization;
use Illuminate\Support\Facades\Config;

/**
 * @property-read Organization $organization
 */
class StoreMollieConnectionRequest extends BaseFormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     * @throws AuthorizationJsonException
     */
    public function authorize(): bool
    {
        $this->maxAttempts = Config::get('forus.throttles.mollie.create.attempts');
        $this->decayMinutes = Config::get('forus.throttles.mollie.create.decay');

        $this->throttleWithKey('to_many_attempts', $this, 'mollie_connection');

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
            'email' => 'required|email|max:191',
            'first_name' => 'required|string|max:191',
            'last_name' => 'required|string|max:191',
            'street' => 'nullable|string|max:191',
            'city' => 'nullable|string|max:191',
            'postcode' => 'nullable|string|max:191',
            'country_code' => 'required|string|max:2',
            'profile_name' => 'required|string|max:191',
            'website' => 'required|url|max:191',
            'phone' => 'required|string|max:191',
        ];
    }
}
