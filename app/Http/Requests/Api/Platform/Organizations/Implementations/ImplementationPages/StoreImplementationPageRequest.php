<?php

namespace App\Http\Requests\Api\Platform\Organizations\Implementations\ImplementationPages;

use App\Models\Implementation;
use App\Models\ImplementationPage;
use App\Models\Organization;
use App\Rules\MediaUidRule;
use App\Services\CmsService\ImplementationBlocks\Validation\ImplementationCmsBlockRuleSet;
use App\Traits\ValidatesFaq;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

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
        $organization = $this->route('organization');
        $implementation = $this->route('implementation');

        return
            $organization instanceof Organization &&
            $implementation instanceof Implementation &&
            Gate::allows('show', $organization) &&
            Gate::allows('updateCMS', [$implementation, $organization]) &&
            Gate::allows('create', [ImplementationPage::class, $implementation, $organization]);
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
        ], $this->cmsBlockRules(), $faqRules);
    }

    /**
     * @return string[]
     */
    public function attributes(): array
    {
        return array_merge(
            parent::attributes(),
            $this->getFaqAttributes(),
        );
    }

    /**
     * @return array
     */
    private function pageTypeRule(): array
    {
        return [
            'required',
            Rule::in(Arr::pluck(ImplementationPage::PAGE_TYPES, 'key')),
            Rule::notIn($this->getRouteImplementation()->pages()->pluck('page_type')->toArray()),
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

    /**
     * @return array
     */
    private function cmsBlockRules(): array
    {
        $pageType = $this->input('page_type');

        if (!in_array($pageType, Arr::pluck(ImplementationPage::PAGE_TYPES, 'key'), true)) {
            return [];
        }

        return ImplementationCmsBlockRuleSet::rules(
            null,
            $pageType,
            $this->input('cms_blocks'),
        );
    }
}
