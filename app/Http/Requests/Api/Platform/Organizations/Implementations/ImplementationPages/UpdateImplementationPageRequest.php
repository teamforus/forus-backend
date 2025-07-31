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
        $states = implode(',', ImplementationPage::STATES);
        $descriptionPositions = implode(',', ImplementationPage::DESCRIPTION_POSITIONS);
        $faqRules = $this->faqRules($this->implementationPage->faq()->pluck('id')->toArray());

        return [
            ...parent::rules(),
            'title' => 'nullable|string|max:200',
            'state' => "nullable|in:$states",
            'description' => ['nullable', ...$this->markdownRules(0, 10000)],
            'description_position' => "nullable|in:$descriptionPositions",
            'description_alignment' => 'nullable|in:left,center,right',
            'external' => 'present|boolean',
            'external_url' => 'nullable|string|max:300',
            'media_uid' => 'nullable|array',
            'media_uid.*' => $this->mediaRule(),
            'blocks_per_row' => 'nullable|integer|min:1|max:3',
            ...$faqRules,
        ];
    }

    /**
     * @return string[]
     */
    public function attributes(): array
    {
        return $this->getFaqAttributes();
    }

    /**
     * @return array
     */
    private function mediaRule(): array
    {
        return [
            'required',
            'string',
            'exists:media,uid',
            new MediaUidRule('cms_media'),
        ];
    }
}
