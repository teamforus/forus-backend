<?php

namespace App\Http\Requests\Api\Platform\Organizations\Funds;

use App\Models\Fund;
use App\Models\Organization;
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
            'auto_requests_validation'  => 'nullable|boolean',
            'default_validator_employee_id' => [
                'nullable',
                Rule::in($availableValidators->toArray()),
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
        ] : [], array_merge(
            $this->faqRules($this->fund->faq()->pluck('id')->toArray()),
            $this->criteriaRule($this->fund->criteria()->pluck('id')->toArray()),
            $this->funConfigsRules(),
            $this->fundFormulaProductsRule(),
        ));
    }
}
