<?php

namespace App\Http\Requests\Api\Platform\Organizations\Implementations;

use App\Rules\MediaUidRule;

class UpdateImplementationPageRequest extends StoreImplementationBlocksRequest
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
        return array_merge(parent::rules(), [
            'content'               => 'nullable|string|max:10000',
            'content_alignment'     => 'nullable|in:left,center,right',
            'external'              => 'present|boolean',
            'external_url'          => 'nullable|string|max:300',
            'media_uid'             => 'nullable|array',
            'media_uid.*'           => $this->mediaRule(),
        ]);
    }

    /**
     * @return array
     */
    private function mediaRule(): array {
        return [
            'required',
            'string',
            'exists:media,uid',
            new MediaUidRule('cms_media')
        ];
    }
}
