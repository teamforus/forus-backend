<?php

namespace App\Http\Requests\Api\Platform\Organizations\Implementations\ImplementationPages;

use App\Http\Requests\BaseFormRequest;
use App\Models\Implementation;
use App\Models\ImplementationPage;
use App\Models\Organization;
use App\Services\CmsService\ImplementationBlocks\Validation\ImplementationCmsBlockRuleSet;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class ValidateImplementationPageCmsBlocksRequest extends BaseFormRequest
{
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
            Gate::allows('updateCMS', [$implementation, $organization]);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules(): array
    {
        return [
            'implementation_page_id' => [
                'nullable',
                'integer',
                Rule::exists('implementation_pages', 'id')->where(
                    'implementation_id',
                    $this->getRouteImplementation()->id,
                )->whereNull('deleted_at'),
            ],
            'page_type' => [
                'nullable',
                Rule::in(Arr::pluck(ImplementationPage::PAGE_TYPES, 'key')),
            ],
            ...$this->cmsBlockRules(),
        ];
    }

    /**
     * @return array
     */
    public function attributes(): array
    {
        return array_merge(
            parent::attributes(),
            ImplementationCmsBlockRuleSet::attributes($this->input('cms_blocks')),
        );
    }

    /**
     * @return ImplementationPage|null
     */
    public function requestedImplementationPage(): ?ImplementationPage
    {
        $pageId = $this->input('implementation_page_id');

        if (!$pageId) {
            return null;
        }

        return $this->getRouteImplementation()->pages()->find($pageId);
    }

    /**
     * @return array
     */
    private function cmsBlockRules(): array
    {
        if ($this->filled('implementation_page_id') && !$this->requestedImplementationPage()) {
            return [];
        }

        return ImplementationCmsBlockRuleSet::rules(
            $this->requestedImplementationPage(),
            $this->input('page_type'),
            $this->input('cms_blocks'),
        );
    }
}
