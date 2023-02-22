<?php

namespace App\Http\Requests\Api\Platform\Organizations\Funds;

use App\Http\Requests\BaseFormRequest;
use App\Models\Fund;
use App\Models\Organization;
use App\Rules\MediaUidRule;
use App\Traits\ValidatesFaq;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

/**
 * @property Organization|null $organization
 */
class StoreFundRequest extends BaseFormRequest
{
    use ValidatesFaq;

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
        $formulaProductsEditable = config('forus.features.dashboard.organizations.funds.formula_products');
        $availableEmployees = $this->organization->employeesOfRoleQuery('validation')->pluck('id');
        $startAfter = now()->addDays(5)->format('Y-m-d');
        $criteriaRules = $this->criteriaRule();
        $funConfigsRules = $this->funConfigsRules();
        $faqRules = $this->getFaqRules([]);

        return array_merge([
            'type'                          => ['required', Rule::in(Fund::TYPES)],
            'name'                          => 'required|between:2,200',
            'media_uid'                     => ['nullable', new MediaUidRule('fund_logo')],
            'description'                   => 'nullable|string|max:15000',
            'description_short'             => 'nullable|string|max:500',
            'start_date'                    => 'required|date_format:Y-m-d|after:' . $startAfter,
            'end_date'                      => 'required|date_format:Y-m-d|after:start_date',
            'notification_amount'           => 'nullable|numeric',
            'faq_title'                     => 'nullable|string|max:200',
            'tag_ids'                       => 'nullable|array',
            'tag_ids.*'                     => 'required|exists:tags,id',
            'allow_fund_requests'           => 'required|boolean',
            'allow_prevalidations'          => 'required|boolean',
            'allow_direct_requests'         => 'required|boolean',
            'request_btn_text'              => 'nullable|string|max:50',
            'external_link_text'            => 'nullable|string|max:50',
            'external_link_url'             => 'nullable|string|max:200',
        ], $funConfigsRules, $faqRules, [
            'auto_requests_validation' => 'nullable|boolean',
            'default_validator_employee_id' => 'nullable|in:' . $availableEmployees->join(','),
        ], $criteriaRules, $formulaProductsEditable ? [
            'formula_products'              => 'nullable|array',
            'formula_products.*'            => 'required|array',
            'formula_products.*.product_id' => [
                'required',
                Rule::exists('products', 'id')->where('unlimited_stock', true)
            ],
            'formula_products.*.record_type_key_multiplier' => [
                'nullable',
                Rule::exists('record_types', 'key')
            ],
        ] : []);
    }

    /**
     * @return string[]
     */
    protected function funConfigsRules(): array
    {
        return [
            'allow_fund_requests' => 'nullable|boolean',
            'allow_prevalidations' => 'nullable|boolean',
            'allow_direct_requests' => 'nullable|boolean',
            'email_required' => 'nullable|boolean',
            'contact_info_enabled' => 'nullable|boolean',
            'contact_info_required' => 'nullable|boolean',
            'contact_info_message_custom' => 'nullable|boolean',
            'contact_info_message_text' => 'nullable|string|max:8000',
        ];
    }

    /**
     * @return array
     */
    private function criteriaRule(): array
    {
        $organization = $this->organization;
        $criteriaEditable = config('forus.features.dashboard.organizations.funds.criteria');
        $validators = $organization->organization_validators()->pluck('id');

        return $criteriaEditable ? [
            'criteria'                      => 'present|array',
            'criteria.*'                    => 'required|array',
            'criteria.*.id'                 => ['nullable', Rule::in([])],
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
}
