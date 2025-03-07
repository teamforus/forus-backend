<?php

namespace App\Http\Requests\Api\Platform\Organizations\Funds;

use App\Models\Fund;
use App\Models\Organization;
use App\Rules\MaxStringRule;
use App\Rules\MediaUidRule;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

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
        $availableValidators = $this->organization->employeesOfRoleQuery('validation')->pluck('id');
        $descriptionPositions = implode(',', Fund::DESCRIPTION_POSITIONS);

        return [
            'name' => 'nullable|between:2,200',
            'media_uid' => ['nullable', new MediaUidRule('fund_logo')],
            'description' => [
                'nullable',
                'string',
                new MaxStringRule(15000),
            ],
            'description_short' => 'nullable|string|max:500',
            'description_position' => "nullable|in:$descriptionPositions",
            'notification_amount' => 'nullable|numeric',
            'faq_title' => 'nullable|string|max:200',
            'tag_ids' => 'nullable|array',
            'tag_ids.*' => 'required|exists:tags,id',
            'request_btn_text' => 'nullable|string|max:50',
            'external_link_text' => 'nullable|string|max:50',
            'external_link_url' => 'nullable|string|max:200',
            'external_page' => 'nullable|boolean',
            'external_page_url' => 'nullable|required_if:external_page,true|string|max:200|url',
            'auto_requests_validation' => 'nullable|boolean',
            'default_validator_employee_id' => [
                'nullable',
                Rule::in($availableValidators->toArray()),
            ],
            'start_date' => $this->fund->isWaiting() ? [
                'nullable',
                'date_format:Y-m-d',
                'after:' . $this->fund->created_at->addDays(5)->format('Y-m-d'),
            ] : [],
            'end_date' => [
                'nullable',
                'date_format:Y-m-d',
                'after:start_date',
            ],
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
