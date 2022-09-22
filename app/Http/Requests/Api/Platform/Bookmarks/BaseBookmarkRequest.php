<?php

namespace App\Http\Requests\Api\Platform\Bookmarks;

use App\Http\Requests\BaseFormRequest;

class BaseBookmarkRequest extends BaseFormRequest
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
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'bookmarkable_id'   => 'integer|min:1',
            'bookmarkable_type' => 'string',
        ];
    }
}
