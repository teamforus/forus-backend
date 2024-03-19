<?php

namespace App\Http\Requests\Api\Platform\Notifications;

use App\Http\Requests\BaseFormRequest;
use App\Services\Forus\Notification\Repositories\NotificationRepo;
use Illuminate\Validation\Rule;

class UpdateNotificationPreferencesRequest extends BaseFormRequest
{


    /**
     * Get the validation rules that apply to the request.
     *
     * @return (\Illuminate\Validation\Rules\In|string)[]
     *
     * @psalm-return array{email_unsubscribed: 'required|boolean', preferences: 'nullable|array', 'preferences.*.key': 'required|', 0: \Illuminate\Validation\Rules\In, 'preferences.*.subscribed': 'required|boolean'}
     */
    public function rules(): array {
        $keys = resolve(NotificationRepo::class)->allPreferenceKeys();

        return [
            'email_unsubscribed'        => 'required|boolean',
            'preferences'               => 'nullable|array',
            'preferences.*.key'         => 'required|', Rule::in($keys),
            'preferences.*.subscribed'  => 'required|boolean'
        ];
    }
}
