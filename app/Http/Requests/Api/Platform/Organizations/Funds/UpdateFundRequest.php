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
 * @property null|Fund $fund
 * @property null|Organization $organization
 */
class UpdateFundRequest extends BaseFormRequest
{
    use ValidatesFaq;

    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize(): bool
    {
        return Gate::allows('update', [$this->fund, $this->organization]);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules(): array
    {
        $formulaProductsEditable = config('forus.features.dashboard.organizations.funds.formula_products');
        $availableValidators = $this->organization->employeesOfRoleQuery('validation')->pluck('id')->toArray();
        $criteriaRules = $this->criteriaRule();
        $fundConfigRules = $this->funConfigsRules();
        $faqRules = $this->getFaqRules($this->fund->faq()->pluck('id')->toArray());

        return array_merge([
            'name'                      => 'nullable|between:2,200',
            'media_uid'                 => ['nullable', new MediaUidRule('fund_logo')],
            'description'               => 'nullable|string|max:15000',
            'description_short'         => 'nullable|string|max:500',
            'notification_amount'       => 'nullable|numeric',
            'faq_title'                 => 'nullable|string|max:200',
            'tag_ids'                   => 'nullable|array',
            'tag_ids.*'                 => 'required|exists:tags,id',
            'request_btn_text'          => 'nullable|string|max:50',
            'external_link_text'        => 'nullable|string|max:50',
            'external_link_url'         => 'nullable|string|max:200',
        ], $fundConfigRules, $faqRules, [
            'auto_requests_validation'  => 'nullable|boolean',
            'default_validator_employee_id' => [
                'nullable', Rule::in($availableValidators)
            ],
        ], ($this->fund && $this->fund->state === Fund::STATE_WAITING) ? [
            'start_date' => [
                'required',
                'date_format:Y-m-d',
                'after:' . $this->fund->created_at->addDays(5)->format('Y-m-d')
            ],
            'end_date' => [
                'required',
                'date_format:Y-m-d',
                'after:start_date'
            ],
        ] : [], $criteriaRules, $formulaProductsEditable ? [
            'formula_products'              => 'nullable|array',
            'formula_products.*'            => [
                'required',
                Rule::exists('products', 'id')->where('unlimited_stock', true)
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
            'criteria'                      => 'nullable|array',
            'criteria.*'                    => 'required|array',
            'criteria.*.id'                 => [
                'nullable', Rule::in($this->fund->criteria()->pluck('id'))
            ],
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
