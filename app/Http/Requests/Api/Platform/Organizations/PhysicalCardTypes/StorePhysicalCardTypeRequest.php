<?php

namespace App\Http\Requests\Api\Platform\Organizations\PhysicalCardTypes;

use App\Http\Requests\BaseFormRequest;
use App\Models\Organization;
use App\Rules\MediaUidRule;

/**
 * @property Organization|null $organization
 */
class StorePhysicalCardTypeRequest extends BaseFormRequest
{
    /**
     * @return bool
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array
     */
    public function rules(): array
    {
        return [
            'name' => 'required|string|max:200',
            'media_uid' => ['sometimes', new MediaUidRule('physical_card_type_photo')],
            'description' => 'nullable|string|max:2000',
            'code_blocks' => 'required|integer|min:1|max:10',
            'code_block_size' => 'required|integer|min:1|max:10',
        ];
    }
}
