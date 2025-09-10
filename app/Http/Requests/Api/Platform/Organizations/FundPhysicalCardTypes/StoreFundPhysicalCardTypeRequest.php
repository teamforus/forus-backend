<?php

namespace App\Http\Requests\Api\Platform\Organizations\FundPhysicalCardTypes;

use App\Http\Requests\Api\Platform\Organizations\Funds\BaseFundRequest;
use App\Models\FundPhysicalCardType;
use App\Models\Organization;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

/**
 * @property Organization $organization
 */
class StoreFundPhysicalCardTypeRequest extends BaseFundRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize(): bool
    {
        return Gate::allows('store', [FundPhysicalCardType::class, $this->organization]);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules(): array
    {
        return [
            'fund_id' => [
                'required',
                Rule::exists('funds', 'id')
                    ->where('organization_id', $this->organization->id),
            ],
            'physical_card_type_id' => [
                'required',
                Rule::exists('physical_card_types', 'id')
                    ->where('organization_id', $this->organization->id),
            ],
            'allow_physical_card_linking' => 'sometimes|boolean',
            'allow_physical_card_requests' => 'sometimes|boolean',
            'allow_physical_card_deactivation' => 'sometimes|boolean',
        ];
    }
}
