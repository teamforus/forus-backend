<?php

namespace App\Http\Requests\Api\Platform\Organizations\Implementations\ImplementationPages;

use App\Models\ImplementationPage;
use App\Rules\MediaUidRule;
use App\Traits\ValidatesFaq;

/**
 * @property-read ImplementationPage $implementationPage
 */
class UpdateImplementationPageRequest extends ValidateImplementationPageBlocksRequest
{
    use ValidatesFaq;

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
        $faqRules = $this->faqRules($this->implementationPage->faq()->pluck('id')->toArray());

        return array_merge(parent::rules(), [
            'state'                 => 'nullable|in:' . implode(',', ImplementationPage::STATES),
            'description'           => 'nullable|string|max:10000',
            'description_alignment' => 'nullable|in:left,center,right',
            'external'              => 'present|boolean',
            'external_url'          => 'nullable|string|max:300',
            'media_uid'             => 'nullable|array',
            'media_uid.*'           => $this->mediaRule(),
        ], $faqRules);
    }

    /**
     * @return array
     */
    private function mediaRule(): array {
        return [
            'required',
            'string',
            'exists:media,uid',
            new MediaUidRule('cms_media'),
        ];
    }

    /**
     * @return string[]
     */
    public function attributes(): array
    {
        return $this->getFaqAttributes();
    }
}
