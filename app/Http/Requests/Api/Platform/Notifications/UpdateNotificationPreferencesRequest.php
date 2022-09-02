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
     * @return bool
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function authorize(): bool
    {
        if ($this->isAuthenticated() && !$this->identity()->email) {
            $this->deny("You can't unsubscribe from the email notifications before you add an email first.");
        }

        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
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
