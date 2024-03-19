<?php

namespace App\Http\Requests\Api\Platform\Organizations\Implementations\SystemNotifications;

use App\Http\Requests\BaseFormRequest;

class IndexSystemNotificationsRequest extends BaseFormRequest
{


    /**
     * Get the validation rules that apply to the request.
     *
     * @return string[]
     *
     * @psalm-return array{per_page: 'numeric|numeric|min:1|max:100'}
     */
    public function rules(): array
    {
        return [
            'per_page' => 'numeric|numeric|min:1|max:100',
        ];
    }
}
