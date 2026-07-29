<?php

namespace App\Services\CmsService\ImplementationBlocks\Rules;

use App\Services\CmsService\ImplementationBlocks\ImplementationCmsBlockService;
use App\Services\CmsService\ImplementationBlocks\Models\ImplementationCmsBlock;
use Illuminate\Contracts\Validation\Rule;

class ImplementationCmsBlockTypeRule implements Rule
{
    /**
     * @param string|null $pageType
     * @param ImplementationCmsBlock|null $existingBlock
     */
    public function __construct(
        protected ?string $pageType,
        protected ?ImplementationCmsBlock $existingBlock = null,
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
        if (!is_string($value)) {
            return true;
        }

        if ($this->existingBlock?->block_type_key === $value) {
            return true;
        }

        $config = ImplementationCmsBlockService::getBlockConfig($value);

        return $config && $this->pageType && $config->isAllowedForPageType($this->pageType);
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
