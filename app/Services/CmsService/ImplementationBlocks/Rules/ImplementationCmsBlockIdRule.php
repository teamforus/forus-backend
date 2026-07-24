<?php

namespace App\Services\CmsService\ImplementationBlocks\Rules;

use App\Models\ImplementationPage;
use Illuminate\Contracts\Validation\Rule;

class ImplementationCmsBlockIdRule implements Rule
{
    /**
     * @param ImplementationPage|null $page
     * @param array $cmsBlockPayload
     */
    public function __construct(
        protected ?ImplementationPage $page,
        protected array $cmsBlockPayload,
    ) {
    }

    /**
     * Determine if the validation rule passes.
     *
     * @param string $attribute
     * @param mixed $value
     * @return bool
     */
    public function passes($attribute, $value): bool
    {
        if (!is_numeric($value)) {
            return true;
        }

        if (!$this->page) {
            return false;
        }

        $cmsBlock = $this->page->cms_blocks()->find($value);

        return $cmsBlock && $cmsBlock->block_type_key === ($this->cmsBlockPayload['block_type_key'] ?? null);
    }

    /**
     * Get the validation error message.
     *
     * @return string
     */
    public function message(): string
    {
        return trans('validation.in');
    }
}
