<?php

namespace App\Http\Requests\Api\Platform\Organizations\PhysicalCardTypes;

use App\Models\Fund;
use App\Models\Organization;
use App\Rules\MediaUidRule;

/**
 * @property null|Fund $fund
 * @property null|Organization $organization
 */
class UpdatePhysicalCardTypeRequest extends StorePhysicalCardTypeRequest
{
    public function rules(): array
    {
        return [
            'name' => 'required|string|max:200',
            'media_uid' => ['sometimes', new MediaUidRule('physical_card_type_photo')],
            'description' => 'nullable|string|max:2000',
        ];
    }
}
