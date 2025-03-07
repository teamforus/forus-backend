<?php

namespace App\Http\Requests\Api\Platform\Notifications;

use App\Http\Requests\BaseFormRequest;
use App\Services\Forus\Notification\Repositories\NotificationRepo;
use Illuminate\Validation\Rule;

class UpdateNotificationPreferencesRequest extends BaseFormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @throws \Illuminate\Auth\Access\AuthorizationException
     * @return bool
     */
    public function authorize(): bool
    {
        if ($this->isAuthenticated() && !$this->identity()->email) {
            $this->deny(trans('requests.notification_unsubscribe.email_required'));
        }

        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules(): array
    {
        $keys = resolve(NotificationRepo::class)->allPreferenceKeys();

        return [
            'email_unsubscribed' => 'required|boolean',
            'preferences' => 'nullable|array',
            'preferences.*.key' => 'required|', Rule::in($keys),
            'preferences.*.subscribed' => 'required|boolean',
        ];
    }
}
