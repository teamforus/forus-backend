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
     * Get the validation rules that apply to the request.
     *
     * @return ((MediaUidRule|\Illuminate\Validation\Rules\In|string)[]|mixed|string)[]
     *
     * @psalm-return array{name: mixed|string, media_uid: list{'nullable', MediaUidRule}|mixed|string, description: mixed|string, description_short: mixed|string, description_position: mixed|string, notification_amount: mixed|string, faq_title: mixed|string, tag_ids: mixed|string, 'tag_ids.*': mixed|string, request_btn_text: mixed|string, external_link_text: mixed|string, external_link_url: mixed|string, external_page: mixed|string, external_page_url: mixed|string, auto_requests_validation: mixed|string, default_validator_employee_id: list{'nullable', \Illuminate\Validation\Rules\In}|mixed|string, start_date?: list{'required', 'date_format:Y-m-d', string}|mixed|string, end_date?: list{'required', 'date_format:Y-m-d', 'after:start_date'}|mixed|string,...}
     */
    public function rules(): array
    {
        $availableValidators = $this->organization->employeesOfRoleQuery('validation')->pluck('id');
        $descriptionPositions = implode(',', Fund::DESCRIPTION_POSITIONS);

        return array_merge([
            'name'                      => 'nullable|between:2,200',
            'media_uid'                 => ['nullable', new MediaUidRule('fund_logo')],
            'description'               => 'nullable|string|max:15000',
            'description_short'         => 'nullable|string|max:500',
            'description_position'      => "nullable|in:$descriptionPositions",
            'notification_amount'       => 'nullable|numeric',
            'faq_title'                 => 'nullable|string|max:200',
            'tag_ids'                   => 'nullable|array',
            'tag_ids.*'                 => 'required|exists:tags,id',
            'request_btn_text'          => 'nullable|string|max:50',
            'external_link_text'        => 'nullable|string|max:50',
            'external_link_url'         => 'nullable|string|max:200',
            'external_page'             => 'nullable|boolean',
            'external_page_url'         => 'nullable|required_if:external_page,true|string|max:200|url',
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
