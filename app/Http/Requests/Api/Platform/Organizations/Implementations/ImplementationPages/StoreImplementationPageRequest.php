<?php

namespace App\Http\Requests\Api\Platform\Organizations\Implementations\ImplementationPages;

use App\Models\Implementation;
use App\Models\ImplementationPage;
use App\Rules\MediaUidRule;
use App\Traits\ValidatesFaq;
use Illuminate\Support\Arr;
use Illuminate\Validation\Rule;

/**
 * @property-read Implementation $implementation
 */
class StoreImplementationPageRequest extends ValidateImplementationPageBlocksRequest
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
        $faqRules = $this->faqRules([]);

        return array_merge(parent::rules(), [
            'title' => 'nullable|string|max:200',
            'state' => "nullable|in:$states",
            'page_type' => $this->pageTypeRule(),
            'description' => 'nullable|string|max:10000',
            'description_position' => "nullable|in:$descriptionPositions",
            'description_alignment' => 'nullable|in:left,center,right',
            'external' => 'present|boolean',
            'external_url' => 'nullable|string|max:300',
            'media_uid' => 'nullable|array',
            'media_uid.*' => $this->mediaRule(),
            'blocks_per_row' => 'nullable|integer|min:1|max:3',
        ], $faqRules);
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
