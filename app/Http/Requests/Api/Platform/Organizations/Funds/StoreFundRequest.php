<?php

namespace App\Http\Requests\Api\Platform\Organizations\Funds;

use App\Http\Requests\BaseFormRequest;
use App\Models\Fund;
use App\Models\Organization;
use App\Rules\MediaUidRule;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

/**
 * Class StoreFundRequest
 * @property Organization|null $organization
 * @package App\Http\Requests\Api\Platform\Organizations\Funds
 */
class StoreFundRequest extends BaseFormRequest
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
        $organization = $this->organization;
        $criteriaEditable = config('forus.features.dashboard.organizations.funds.criteria');
        $formulaProductsEditable = config('forus.features.dashboard.organizations.funds.formula_products');

        $availableEmployees = $this->organization->employeesOfRoleQuery('validation')->pluck('id');
        $start_after = now()->addDays(5)->format('Y-m-d');
        $validators = $organization->organization_validators()->pluck('id');

        $criteriaRules = $criteriaEditable ? [
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

        return array_merge([
            'type'                          => ['required', Rule::in(Fund::TYPES)],
            'name'                          => 'required|between:2,200',
            'media_uid'                     => ['nullable', new MediaUidRule('fund_logo')],
            'description'                   => 'nullable|string|max:15000',
            'description_short'             => 'nullable|string|max:280',
            'description_media_uid'         => 'nullable|array',
            'description_media_uid.*'       => $this->mediaRule(),
            'start_date'                    => 'required|date_format:Y-m-d|after:' . $start_after,
            'end_date'                      => 'required|date_format:Y-m-d|after:start_date',
            'notification_amount'           => 'nullable|numeric',
            'faq_title'                     => 'nullable|string|max:200',
            'faq'                           => 'present|array',
            'faq.*'                         => 'required|array',
            'faq.*.id'                      => 'nullable|in:',
            'faq.*.title'                   => 'required|string|max:200',
            'faq.*.description'             => 'required|string|max:5000',
            'allow_fund_requests'           => 'required|boolean',
            'allow_prevalidations'          => 'required|boolean',
            'allow_direct_requests'         => 'required|boolean',
            'request_btn_text'              => 'nullable|string|max:50',
            'external_link_text'            => 'nullable|string|max:50',
            'external_link_url'             => 'nullable|string|max:200',
        ], [
            'auto_requests_validation' => 'nullable|boolean',
            'default_validator_employee_id' => 'nullable|in:' . $availableEmployees->join(','),
        ], $criteriaRules, $formulaProductsEditable ? [
            'formula_products'              => 'nullable|array',
            'formula_products.*'            => [
                'required',
                Rule::exists('products', 'id')->where('unlimited_stock', true)
            ],
        ] : []);
    }

    /**
     * @return array
     */
    private function mediaRule(): array {
        return [
            'required',
            'string',
            'exists:media,uid',
            new MediaUidRule('cms_media')
        ];
    }

    /**
     * @return string[]
     */
    public function attributes(): array
    {
        return [
            'criteria.*.value' => 'Waarde'
        ];
    }
}
