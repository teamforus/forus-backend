<?php

namespace App\Http\Requests\Api\Platform\Organizations\Implementations\ImplementationPages;

use App\Models\Implementation;
use App\Models\ImplementationPage;
use App\Rules\MediaUidRule;
use Illuminate\Support\Arr;
use Illuminate\Validation\Rule;

/**
 * @property-read Implementation $implementation
 */
class StoreImplementationPageRequest extends ValidateImplementationPageBlocksRequest
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
            'state'                 => 'nullable|in:' . implode(',', ImplementationPage::STATES),
            'page_type'             => $this->pageTypeRule(),
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
    private function pageTypeRule(): array
    {
        return [
            'required',
            Rule::in(Arr::pluck(ImplementationPage::PAGE_TYPES, 'key')),
            Rule::notIn($this->implementation->pages()->pluck('page_type')->toArray()),
        ];
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