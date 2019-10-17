<?php

namespace App\Http\Requests\Api\Platfrom\Notifications;

use App\Services\Forus\Notification\Interfaces\INotificationRepo;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateNotificationPreferencesRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @param INotificationRepo $notificationRepo
     * @return array
     */
    public function rules(
        INotificationRepo $notificationRepo
    ) {
        return [
            'email_unsubscribed'        => 'required|boolean',
            'preferences'               => 'nullable|array',
            'preferences.*.key'         => [
                'required',
                Rule::in($notificationRepo->mailTypeKeys())
            ],
            'preferences.*.subscribed'  => [
                'required',
                'boolean'
            ]
        ];
    }
}
