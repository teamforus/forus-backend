<?php

namespace App\Services\CmsService\ImplementationBlocks\Rules;

use App\Services\CmsService\ImplementationBlocks\Models\ImplementationCmsBlock;
use App\Services\CmsService\ImplementationBlocks\Models\ImplementationCmsBlockItem;
use Illuminate\Contracts\Validation\Rule;

class ImplementationCmsBlockItemIdRule implements Rule
{
    /**
     * @param ImplementationCmsBlock|null $block
     */
    public function __construct(
        protected ?ImplementationCmsBlock $block,
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

        $item = ImplementationCmsBlockItem::query()->find($value);

        return $item && $this->block && $item->implementation_cms_block_id === $this->block->id;
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
