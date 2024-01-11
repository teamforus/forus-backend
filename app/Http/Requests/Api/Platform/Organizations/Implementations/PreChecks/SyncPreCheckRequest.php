<?php

namespace App\Http\Requests\Api\Platform\Organizations\Implementations\PreChecks;

use App\Http\Requests\BaseFormRequest;
use App\Models\Organization;
use Illuminate\Validation\Rule;

/**
 * @property-read Organization $organization
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
            'pre_check_title' => 'required|string|max:50',
            'pre_check_enabled' => 'nullable|boolean',
            'pre_check_description' => 'nullable|string|max:1000',
            ...$this->preCheckRules(),
            ...$this->preCheckRecordRules(),
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
            'pre_checks.*.record_types.*'=> 'nullable|array',
            'pre_checks.*.record_types.*.title' => 'required|string|max:100',
            'pre_checks.*.record_types.*.title_short' => 'required|string|max:40',
            'pre_checks.*.record_types.*.description' => 'nullable|string|max:1000',
            'pre_checks.*.record_types.*.record_type_key' => 'required|string|exists:record_types,key',
        ];
    }

    /**
     * @return string[]
     */
    public function attributes(): array
    {
        return [
            'pre_checks.*.title' => 'title',
            'pre_checks.*.description' => 'description',
            'pre_checks.*.record_types.*.title' => 'title',
            'pre_checks.*.record_types.*.title_short' => 'short title',
            'pre_checks.*.record_types.*.record_type_key' => 'key',
        ];
    }
}
