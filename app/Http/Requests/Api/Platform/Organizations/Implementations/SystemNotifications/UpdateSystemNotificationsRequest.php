<?php

namespace App\Http\Requests\Api\Platform\Organizations\Implementations\SystemNotifications;

use App\Http\Requests\BaseFormRequest;
use App\Models\Implementation;
use App\Models\SystemNotification;
use App\Rules\SystemNotificationTemplateContentRule;
use App\Rules\SystemNotificationTemplateTitleRule;
use Illuminate\Validation\Rule;

/**
 * @property-read Implementation $implementation
 * @property-read SystemNotification $system_notification
 */
class UpdateSystemNotificationsRequest extends BaseFormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize(): bool
    {
        return $this->system_notification->editable;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules(): array
    {
        return [
            'enable_all' => 'nullable|boolean',
            'enable_mail' => 'nullable|boolean',
            'enable_push' => 'nullable|boolean',
            'enable_database' => 'nullable|boolean',

            'templates' => 'nullable|array',
            'templates.*.type' => 'required|string|in:mail,push,database',
            'templates.*.formal' => 'required|boolean',
            'templates.*.title' => ['required', 'string', new SystemNotificationTemplateTitleRule()],
            'templates.*.fund_id' => $this->fundIdRules($this->implementation),
            'templates.*.content' => ['required', 'string', new SystemNotificationTemplateContentRule()],

            'templates_remove' => 'nullable|array',
            'templates_remove.*.type' => 'required|string|in:mail,push,database',
            'templates_remove.*.formal' => 'required|boolean',
            'templates_remove.*.fund_id' => $this->fundIdRules($this->implementation),
        ];
    }

    /**
     * @param Implementation $implementation
     * @return array
     */
    protected function fundIdRules(Implementation $implementation): array
    {
        if ($implementation->allow_per_fund_notification_templates) {
            $fundIds = $implementation->funds()->pluck('funds.id')->toArray();
        } else {
            $fundIds = [];
        }

        return [
            'nullable',
            Rule::exists('funds', 'id')->whereIn('id', $fundIds),
        ];
    }
}
