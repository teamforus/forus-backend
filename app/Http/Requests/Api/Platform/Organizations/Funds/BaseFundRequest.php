<?php

namespace App\Http\Requests\Api\Platform\Organizations\Funds;

use App\Http\Requests\BaseFormRequest;
use App\Models\Fund;
use App\Models\FundConfig;
use App\Models\Organization;
use App\Models\PhysicalCardType;
use App\Rules\FundCriteria\FundCriteriaKeyRule;
use App\Rules\FundCriteria\FundCriteriaMaxRule;
use App\Rules\FundCriteria\FundCriteriaMinRule;
use App\Rules\FundCriteria\FundCriteriaOperatorRule;
use App\Rules\FundCriteria\FundCriteriaValueRule;
use App\Rules\MediaUidRule;
use App\Traits\ValidatesFaq;
use Illuminate\Support\Facades\Config;
use Illuminate\Validation\Rule;

/**
 * @property Organization|null $organization
 * @property Fund|null $fund
 */
abstract class BaseFundRequest extends BaseFormRequest
{
    use ValidatesFaq;

    /**
     * @return string[]
     */
    public function attributes(): array
    {
        return array_merge([
            'criteria.*.value' => 'Waarde',
        ], $this->getFaqAttributes());
    }

    /**
     * @return array
     */
    public function messages(): array
    {
        return [
            'external_page_url.required_if' => 'Het external URL veld is verplicht',
        ];
    }

    /**
     * @return string[]
     */
    protected function baseRules(bool $updating): array
    {
        $availableEmployees = $this->organization->employeesOfRoleQuery('validation')->pluck('id');
        $descriptionPositions = implode(',', Fund::DESCRIPTION_POSITIONS);

        return [
            'name' => [$updating ? 'sometimes' : 'required', 'between:2,200'],
            'media_uid' => ['sometimes', new MediaUidRule('fund_logo')],
            'description' => ['nullable', ...$this->markdownRules(0, 15000)],
            'description_short' => 'sometimes|string|max:500',
            'description_position' => ['sometimes', 'in:' . $descriptionPositions],

            'notification_amount' => 'nullable|numeric|min:0|max:1000000',
            'faq_title' => 'nullable|string|max:200',
            'tag_ids' => 'sometimes|array',
            'tag_ids.*' => 'sometimes|exists:tags,id',

            'request_btn_text' => 'sometimes|string|max:50',
            'external_link_text' => 'nullable|string|max:50',

            'external_link_url' => 'nullable|string|max:200',
            'external_page' => 'nullable|boolean',
            'external_page_url' => 'nullable|required_if:external_page,true|string|max:200|url',

            'auto_requests_validation' => 'sometimes|boolean',
            'default_validator_employee_id' => ['nullable', Rule::in($availableEmployees->toArray())],

            'allow_fund_requests' => 'sometimes|boolean',
            'allow_prevalidations' => 'sometimes|boolean',
            'allow_direct_requests' => 'sometimes|boolean',
        ];
    }

    /**
     * @return array
     */
    protected function fundFormulaProductsRules(): array
    {
        $formulaProductsEditable = Config::get('forus.features.dashboard.organizations.funds.formula_products');

        return $formulaProductsEditable ? [
            'formula_products' => 'nullable|array',
            'formula_products.*' => 'required|array',
            'formula_products.*.product_id' => [
                'required',
                Rule::exists('products', 'id')->where('unlimited_stock', true),
            ],
            'formula_products.*.record_type_key_multiplier' => 'nullable|exists:record_types,key',
        ] : [];
    }

    /**
     * @return array
     */
    protected function physicalCardTypeRules(): array
    {
        return [
            'fund_request_physical_card_enable' => 'sometimes|boolean',
            'fund_request_physical_card_type_id' => [
                'nullable',
                'sometimes',
                Rule::exists('physical_card_types', 'id')
                    ->whereIn(
                        'physical_card_types.id',
                        PhysicalCardType::query()
                            ->whereRelation('funds', 'organization_id', $this->organization->id)
                            ->select('id'),
                    ),
            ],
        ];
    }

    /**
     * @return string[]
     */
    protected function funConfigsRules(): array
    {
        $auth2FAPolicies = implode(',', FundConfig::AUTH_2FA_POLICIES);

        return [
            'hide_meta' => 'nullable|boolean',
            'email_required' => 'nullable|boolean',
            'allow_fund_requests' => 'nullable|boolean',
            'allow_prevalidations' => 'nullable|boolean',
            'allow_direct_requests' => 'nullable|boolean',
            'voucher_amount_visible' => 'nullable|boolean',
            'contact_info_enabled' => 'nullable|boolean',
            'contact_info_required' => 'nullable|boolean',
            'contact_info_message_custom' => 'nullable|boolean',
            'contact_info_message_text' => 'nullable|string|max:8000',
            'criteria_label_requirement_show' => 'nullable|in:both,optional,required',

            // auth 2fa
            'auth_2fa_policy' => "nullable|in:$auth2FAPolicies",
            'auth_2fa_remember_ip' => 'nullable|boolean',
            'auth_2fa_restrict_emails' => 'nullable|boolean',
            'auth_2fa_restrict_auth_sessions' => 'nullable|boolean',
            'auth_2fa_restrict_reimbursements' => 'nullable|boolean',
            'provider_products_required' => 'nullable|boolean',

            'allow_provider_sign_up' => 'nullable|boolean',
            'allow_physical_cards' => 'nullable|boolean',

            // help columns
            ...$this->fundConfigHelpRules(),
        ];
    }

    /**
     * @param array $criteriaIds
     * @return array
     */
    protected function criteriaRule(array $criteriaIds = []): array
    {
        $organization = $this->organization;
        $criteriaEditable = Config::get('forus.features.dashboard.organizations.funds.criteria');

        return $criteriaEditable ? [
            'criteria' => 'nullable|array',
            'criteria.*' => 'required|array',
            'criteria.*.id' => ['nullable', Rule::in($criteriaIds)],

            'criteria.*.operator' => ['present', new FundCriteriaOperatorRule($this, $organization)],
            'criteria.*.record_type_key' => ['required', new FundCriteriaKeyRule($this, $organization)],
            'criteria.*.value' => ['nullable', new FundCriteriaValueRule($this, $organization)],

            'criteria.*.optional' => 'nullable|boolean',
            'criteria.*.show_attachment' => 'nullable|boolean',

            'criteria.*.min' => ['nullable', new FundCriteriaMinRule($this, $organization)],
            'criteria.*.max' => ['nullable', new FundCriteriaMaxRule($this, $organization)],

            'criteria.*.title' => 'nullable|string|max:100',
            'criteria.*.label' => 'nullable|string|min:1|max:200',
            'criteria.*.description' => ['nullable', ...$this->markdownRules(0, 4000)],
            'criteria.*.extra_description' => ['nullable', ...$this->markdownRules(0, 4000)],
        ] : [];
    }

    /**
     * @return array
     */
    private function fundConfigHelpRules(): array
    {
        return [
            'help_enabled' => 'nullable|boolean',
            'help_title' => 'nullable|required_if_accepted:help_enabled|string|max:200',
            'help_block_text' => 'nullable|required_if_accepted:help_enabled|string|max:200',
            'help_button_text' => 'nullable|required_if_accepted:help_enabled|string|max:200',
            'help_description' => 'nullable|required_if_accepted:help_enabled|string',
            'help_show_email' => 'nullable|boolean',
            'help_show_phone' => 'nullable|boolean',
            'help_show_website' => 'nullable|boolean',
            'help_show_chat' => 'nullable|boolean',

            ...$this->get('help_enabled', false) ? [
                'help_email' => 'nullable|required_if_accepted:help_show_email|email|max:200',
                'help_phone' => 'nullable|required_if_accepted:help_show_phone|string|max:200',
                'help_website' => 'nullable|required_if_accepted:help_show_website|url|max:200|starts_with:https://',
                'help_chat' => 'nullable|required_if_accepted:help_show_chat|url|max:200|starts_with:https://',
            ] : [
                'help_email' => 'nullable|email|max:200',
                'help_phone' => 'nullable|string|max:200',
                'help_website' => 'nullable|url|max:200|starts_with:https://',
                'help_chat' => 'nullable|url|max:200|starts_with:https://',
            ],
        ];
    }
}
