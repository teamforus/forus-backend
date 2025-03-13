<?php

namespace App\Http\Requests\Api\Platform\Profile;

use AllowDynamicProperties;
use App\Exceptions\AuthorizationJsonException;
use App\Http\Requests\BaseFormRequest;
use Illuminate\Support\Facades\Config;

#[AllowDynamicProperties]
class UpdateProfileRequest extends BaseFormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @throws AuthorizationJsonException
     * @return bool
     */
    public function authorize(): bool
    {
        $this->maxAttempts = Config::get('forus.throttles.update_profile.attempts');
        $this->decayMinutes = Config::get('forus.throttles.update_profile.decay');

        $this->throttleWithKey('to_many_attempts', $this, 'auth');

        return
            !$this->implementation()->isGeneral() &&
            $this->implementation()?->organization?->allow_profiles;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules(): array
    {
        return [
            'mobile' => 'sometimes|nullable|string|min:2|max:40',
            'telephone' => 'sometimes|nullable|string|min:2|max:40',
            'city' => 'sometimes|nullable|string|city_name',
            'street' => 'sometimes|nullable|string|street_name',
            'house_number' => 'sometimes|nullable|string|house_number',
            'house_number_addition' => 'sometimes|nullable|string|house_addition',
            'postal_code' => 'sometimes|nullable|string|postcode',
        ];
    }
}
