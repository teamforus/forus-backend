<?php

namespace App\Http\Requests\Api\Platform\Bookmarks;

class RemoveBookmarkRequest extends BaseBookmarkRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return parent::rules();
    }
}
