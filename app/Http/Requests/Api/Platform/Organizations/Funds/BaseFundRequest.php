<?php

namespace App\Http\Requests\Api\Platform\Organizations\Funds;

use App\Http\Requests\BaseFormRequest;
use App\Models\FundConfig;
use App\Models\Organization;
use App\Traits\ValidatesFaq;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Config;

/**
 * @property Organization|null $organization
 */
abstract class BaseFundRequest extends BaseFormRequest
{
    use ValidatesFaq;

    /**
     * @return array
     */
    protected function fundFormulaProductsRule(): array
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
            'contact_info_enabled' => 'nullable|boolean',
            'contact_info_required' => 'nullable|boolean',
            'contact_info_message_custom' => 'nullable|boolean',
            'contact_info_message_text' => 'nullable|string|max:8000',

            // auth 2fa
            'auth_2fa_policy' => "nullable|in:$auth2FAPolicies",
            'auth_2fa_remember_ip' => 'nullable|boolean',
            'auth_2fa_restrict_emails' => 'nullable|boolean',
            'auth_2fa_restrict_auth_sessions' => 'nullable|boolean',
            'auth_2fa_restrict_reimbursements' => 'nullable|boolean',
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
        $validators = $organization->organization_validators()->pluck('id');

        return $criteriaEditable ? [
            'criteria'                      => 'nullable|array',
            'criteria.*'                    => 'required|array',
            'criteria.*.id'                 => ['nullable', Rule::in($criteriaIds)],
            'criteria.*.operator'           => 'required|in:=,<,>',
            'criteria.*.record_type_key'    => 'required|exists:record_types,key',
            'criteria.*.value'              => 'required|string|between:1,20',
            'criteria.*.show_attachment'    => 'nullable|boolean',
            'criteria.*.title'              => 'nullable|string|max:100',
            'criteria.*.description'        => 'nullable|string|max:4000',
            'criteria.*.validators'         => 'nullable|array',
            'criteria.*.validators.*'       => Rule::in($validators->toArray())
        ] : [];
    }

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
}
