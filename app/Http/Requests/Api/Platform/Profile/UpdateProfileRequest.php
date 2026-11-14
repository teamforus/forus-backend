<?php

namespace App\Http\Requests\Api\Platform\Profile;

use AllowDynamicProperties;
use App\Exceptions\AuthorizationJsonException;
use App\Http\Requests\BaseFormRequest;
use App\Models\RecordType;
use App\Rules\ProfileRecordValueRule;
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
        $keys = [
            'mobile', 'telephone', 'city', 'street', 'house_number', 'house_number_addition', 'postal_code',
        ];

        $recordTypes = RecordType::searchRecordTypes(
            organizationId: $this->implementation()->organization_id,
            profileRecords: true,
            requesterEditable: true,
            includeKeys: $keys,
        );

        $rules = [];

        foreach ($keys as $key) {
            $recordType = $recordTypes->get($key);

            $rules[$key] = $recordType ?
                ['sometimes', 'nullable', 'string', new ProfileRecordValueRule($recordType)] :
                ['missing'];
        }

        return $rules;
    }
}
