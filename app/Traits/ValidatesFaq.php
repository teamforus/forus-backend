<?php

namespace App\Traits;

use App\Models\Fund;
use App\Rules\MaxStringRule;
use App\Rules\MediaUidRule;
use Illuminate\Validation\Rule;

trait ValidatesFaq
{
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
            'description' => ['nullable', 'string', new MaxStringRule(15000)],
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
     * @return string[]
     */
    protected function faqRules(?array $allowedIds = null): array
    {
        return array_merge([
            'faq' => 'nullable|array',
            'faq.*' => 'required|array',
            'faq.*.title' => 'required|string|max:200',
            'faq.*.description' => 'required|string|max:5000',
        ], $allowedIds !== null ? [
            'faq.*.id' => [
                'nullable',
                Rule::exists('faq', 'id'),
                Rule::in($allowedIds),
            ],
        ] : []);
    }

    /**
     * @return array
     */
    protected function getFaqAttributes(): array
    {
        $keys = array_dot(array_keys($this->rules()));

        return array_combine($keys, array_map(static function ($key) {
            $value = last(explode('.', $key));

            return trans_fb('validation.attributes.' . $value, $value);
        }, $keys));
    }
}
