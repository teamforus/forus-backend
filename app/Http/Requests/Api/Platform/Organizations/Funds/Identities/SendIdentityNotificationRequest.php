<?php

namespace App\Http\Requests\Api\Platform\Organizations\Funds\Identities;

use App\Traits\ThrottleWithMeta;

class SendIdentityNotificationRequest extends ExportIdentitiesRequest
{
    use ThrottleWithMeta;

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
     * @return array<string, string>
     * @throws \App\Exceptions\AuthorizationJsonException
     */
    public function rules(): array
    {
        $this->maxAttempts = env('FUND_CUSTOM_NOTIFICATION_ATTEMPTS', 10);
        $this->decayMinutes = env('FUND_CUSTOM_NOTIFICATION_DECAY', 60 * 24);

        if ($this->input('target') !== 'self') {
            $this->throttleWithKey('to_many_attempts', $this, 'fund_custom_notification');
        }

        return array_merge(parent::rules(), [
            'subject' => 'required|string|min:5|max:400',
            'content' => 'required|string|min:5|max:16384',
        ]);
    }
}
