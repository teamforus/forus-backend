<?php

namespace App\Http\Requests\Api\Platform\Organizations\Funds;

use App\Models\Fund;
use App\Models\Organization;
use Illuminate\Support\Facades\Gate;

/**
 * @property null|Fund $fund
 * @property null|Organization $organization
 */
class UpdateFundRequest extends BaseFundRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize(): bool
    {
        return Gate::any(['update', 'updateTexts'], [$this->fund, $this->organization]);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules(): array
    {
        $startAfter = $this->fund->created_at->addDays(5)->format('Y-m-d');

        return [
            ...$this->baseRules(updating: true),

            'start_date' => $this->fund->isWaiting() ? 'required|date_format:Y-m-d|after:' . $startAfter : [],
            'end_date' => 'nullable|date_format:Y-m-d|after:start_date',

            ...$this->customAndPresetAmountRules(),
            ...$this->faqRules($this->fund->faq()->pluck('id')->toArray()),
            ...$this->criteriaRule($this->fund->criteria()->pluck('id')->toArray()),
            ...$this->funConfigsRules(),
            ...$this->fundFormulaProductsRule(),

        ];
    }

    /**
     * @return array
     */
    public function customAndPresetAmountRules(): array
    {
        $maxPerVoucherAmount = currency_format($this->fund?->fund_config?->limit_voucher_total_amount ?: 0);

        return [
            'allow_custom_amounts' => 'nullable|boolean',
            'allow_preset_amounts' => 'nullable|boolean',
            'allow_custom_amounts_validator' => 'nullable|boolean',
            'allow_preset_amounts_validator' => 'nullable|boolean',
            'custom_amount_min' => [
                'nullable',
                'required_if_accepted:allow_custom_amounts',
                'required_if_accepted:allow_custom_amounts_validator',
                'numeric',
                'min:0',
                'lte:custom_amount_max',
                "max:$maxPerVoucherAmount",
            ],
            'custom_amount_max' => [
                'nullable',
                'required_if_accepted:allow_custom_amounts',
                'required_if_accepted:allow_custom_amounts_validator',
                'numeric',
                'min:0',
                'gte:custom_amount_min',
                "max:$maxPerVoucherAmount",
            ],
            'amount_presets' => 'nullable|array',
            'amount_presets.*.id' => 'nullable|in:' . $this->fund->amount_presets()->pluck('id')->join(','),
            'amount_presets.*.name' => 'required|string|max:200',
            'amount_presets.*.amount' => "required|numeric|min:0|max:$maxPerVoucherAmount",
        ];
    }

    /**
     * @return array|string[]
     */
    public function attributes(): array
    {
        return [
            ...parent::attributes(),
            'custom_amount_min' => 'Minimaal bedrag',
            'custom_amount_max' => 'Maximaal bedrag',
        ];
    }

    /**
     * @return array
     */
    public function messages(): array
    {
        return [
            ...parent::messages(),
            'custom_amount_min.required_if_accepted' => trans('validation.required'),
            'custom_amount_max.required_if_accepted' => trans('validation.required'),
        ];
    }
}
