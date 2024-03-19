<?php

namespace App\Http\Requests\Api\Platform\Organizations\Announcements;

use App\Http\Requests\BaseFormRequest;

class IndexAnnouncementRequest extends BaseFormRequest
{


    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     *
     * @psalm-return array<never, never>
     */
    public function rules(): array
    {
        return [];
    }
}
