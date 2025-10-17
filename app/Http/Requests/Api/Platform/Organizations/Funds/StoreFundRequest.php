<?php

namespace App\Http\Requests\Api\Platform\Organizations\Funds;

use App\Models\Fund;
use App\Models\FundConfig;
use App\Models\Organization;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

/**
 * @property Organization|null $organization
 */
class StoreFundRequest extends BaseFundRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize(): bool
    {
        return Gate::allows('store', [Fund::class, $this->organization]);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules(): array
    {
        $startAfter = now()->addDays(5)->format('Y-m-d');

        return [
            ...$this->baseRules(updating: false),

            'external' => 'nullable|boolean',
            'outcome_type' => ['required', Rule::in(FundConfig::OUTCOME_TYPES)],
            'start_date' => 'required|date_format:Y-m-d|after:' . $startAfter,
            'end_date' => 'required|date_format:Y-m-d|after:start_date',

            ...$this->faqRules([]),
            ...$this->criteriaRule(),
            ...$this->funConfigsRules(),
            ...$this->fundFormulaProductsRules(),
        ];
    }
}
