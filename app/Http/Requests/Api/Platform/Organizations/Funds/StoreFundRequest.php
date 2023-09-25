<?php

namespace App\Http\Requests\Api\Platform\Organizations\Funds;

use App\Models\Fund;
use App\Models\Organization;
use App\Rules\MediaUidRule;
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
        $availableEmployees = $this->organization->employeesOfRoleQuery('validation')->pluck('id');
        $startAfter = now()->addDays(5)->format('Y-m-d');
        $descriptionPositions = implode(',', Fund::DESCRIPTION_POSITIONS);

        return array_merge([
            'type'                          => ['required', Rule::in(Fund::TYPES)],
            'name'                          => 'required|between:2,200',
            'media_uid'                     => ['nullable', new MediaUidRule('fund_logo')],
            'description'                   => 'nullable|string|max:15000',
            'description_position'          => "nullable|in:$descriptionPositions",
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
            'external_page'                 => 'nullable|boolean',
            'external_page_url'             => 'nullable|required_if:external_page,true|string|max:200|url',
            'auto_requests_validation'      => 'nullable|boolean',
            'default_validator_employee_id' => 'nullable|in:' . $availableEmployees->join(','),
        ], array_merge(
            $this->faqRules([]),
            $this->criteriaRule(),
            $this->funConfigsRules(),
            $this->fundFormulaProductsRule(),
        ));
    }
}
