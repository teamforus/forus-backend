<?php

namespace App\Http\Requests\Api\Platform\Organizations\Implementations\SystemNotifications;

use App\Http\Requests\BaseFormRequest;

class IndexSystemNotificationsRequest extends BaseFormRequest
{
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
     * @return array
     */
    public function rules(): array
    {
        return [
            'per_page' => 'numeric|numeric|min:1|max:100',
        ];
    }
}
