<?php

namespace App\Http\Requests\Api\Platform\Organizations\Implementations\PreChecks;

use App\Http\Requests\BaseFormRequest;
use App\Models\Implementation;
use App\Models\Organization;
use Illuminate\Validation\Rule;

/**
 * @property-read Organization $organization
 * @property-read Implementation $implementation
 */
class SyncPreCheckRequest extends BaseFormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules(): array
    {
        return [
            'pre_check_title' => 'sometimes|required|string|max:50',
            'pre_check_enabled' => 'nullable|boolean',
            'pre_check_description' => 'nullable|string|max:1000',
            ...$this->preCheckRules(),
            ...$this->preCheckRecordRules(),
            ...$this->preCheckRecordSettingRules(),
            ...$this->preCheckFundExclusionRules(),
            ...$this->preCheckFundExclusionRemoveRules(),
        ];
    }

    /**
     * @return string[]
     */
    private function preCheckRules(): array
    {
        return [
            'pre_checks.*' => 'nullable|array',
            'pre_checks.*.id' => [
                'nullable',
                Rule::exists('pre_checks', 'id')->whereIn(
                    'implementation_id',
                    $this->organization->implementations->pluck('id')->toArray(),
                ),
            ],
            'pre_checks.*.title' => 'required|string|max:100',
            'pre_checks.*.title_short' => 'required|string|max:30',
            'pre_checks.*.default' => 'nullable|boolean',
            'pre_checks.*.description' => 'nullable|string|max:1000',
        ];
    }

    /**
     * @return string[]
     */
    private function preCheckRecordRules(): array
    {
        return [
            'pre_checks.*.record_types.*' => 'nullable|array',
            'pre_checks.*.record_types.*.title' => 'required|string|max:100',
            'pre_checks.*.record_types.*.title_short' => 'required|string|max:40',
            'pre_checks.*.record_types.*.description' => 'nullable|string|max:1000',
            'pre_checks.*.record_types.*.record_type_key' => [
                'required',
                'string',
                Rule::exists('record_types', 'key')->where('pre_check', true),
            ],
        ];
    }

    /**
     * @return string[]
     */
    private function preCheckRecordSettingRules(): array
    {
        return [
            'pre_checks.*.record_types.*.record_settings.*' => 'nullable|array',
            'pre_checks.*.record_types.*.record_settings.*.fund_id' => [
                'nullable',
                Rule::exists('funds', 'id')->where('organization_id', $this->organization->id),
            ],
            'pre_checks.*.record_types.*.record_settings.*.is_knock_out' => 'required|boolean',
            'pre_checks.*.record_types.*.record_settings.*.description' => 'nullable|string|max:1000',
            'pre_checks.*.record_types.*.record_settings.*.impact_level' => 'required|int|min:0|max:100',
        ];
    }

    /**
     * @return string[]
     */
    private function preCheckFundExclusionRules(): array
    {
        return [
            'exclusion' => 'nullable|array',
            'exclusion.fund_id' => [
                'required_with::exclude_fund',
                'exists:funds,id',
                Rule::in($this->organization->funds->pluck('id')->toArray()),
            ],
            'exclusion.excluded' => [
                'nullable',
                'boolean',
            ],
            'exclusion.remove' => [
                'nullable',
                'boolean',
            ],
            'exclusion.note' => [
                'required_if:exclusion.excluded,false',
                'nullable',
                'string',
                'min:5',
                'max:2000',
            ],
        ];
    }

    /**
     * @return string[]
     */
    private function preCheckFundExclusionRemoveRules(): array
    {
        return [
            'exclusions_remove' => 'nullable|array',
            'exclusions_remove.*' => [
                'exists:funds,id',
                Rule::in($this->organization->funds->pluck('id')->toArray()),
            ],
        ];
    }

    /**
     * @return string[]
     */
    public function attributes(): array
    {
        return [
            'pre_checks.*.title' => 'title',
            'pre_checks.*.title_short' => 'short title',
            'pre_checks.*.description' => 'description',
            'pre_checks.*.record_types.*.title' => 'title',
            'pre_checks.*.record_types.*.title_short' => 'short title',
            'pre_checks.*.record_types.*.record_type_key' => 'key',
            'exclusion_remove.*' => 'fonds',
            'exclusion.fund_id' => 'fonds',
            'exclusion.note' => 'uitleg',
        ];
    }

    public function messages(): array
    {
        return [
            ...parent::messages(),
            'exclusion.note.required_if' => trans('validation.required'),
        ];
    }
}
