<?php

namespace App\Http\Requests\Api\Platform;

use App\Http\Requests\BaseFormRequest;
use App\Models\Implementation;
use Illuminate\Database\Query\Builder;
use Illuminate\Validation\Rule;

/**
 * Class SearchProvidersRequest
 * @package App\Http\Requests\Api\Platform
 */
class SearchProvidersRequest extends BaseFormRequest
{
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
     * @return array
     */
    public function rules(): array
    {
        return [
            'q'                 => 'nullable|string',
            'business_type_id'  => 'nullable|exists:business_types,id',
            'fund_id'           => $this->fundIdRules($this->implementation_model()),
            'per_page'          => 'numeric|max:1000',
            'order_by'          => 'nullable|in:created_at',
            'order_by_dir'      => 'nullable|in:asc,desc',
        ];
    }

    /**
     * @param Implementation|null $implementation
     * @return array
     */
    protected function fundIdRules(?Implementation $implementation = null): array {
        return [
            'nullable',
            Rule::exists('funds', 'id')->where(function(Builder $builder) use ($implementation) {
                if ($implementation && !$implementation->isGeneral()) {
                    $builder->addWhereExistsQuery($implementation->funds()->getBaseQuery());
                }
            })
        ];
    }
}
