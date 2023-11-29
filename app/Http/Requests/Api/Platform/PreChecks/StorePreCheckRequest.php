<?php

namespace App\Http\Requests\Api\Platform\PreChecks;

use App\Http\Requests\BaseFormRequest;

class StorePreCheckRequest extends BaseFormRequest
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
        return array_merge([
            'pre_check_enabled' => 'nullable|boolean',
            'pre_check_title' => 'required|string|max:50',
            'pre_check_description'   => 'nullable|string|max:1000',
        ], $this->preCheckRules(), $this->preCheckRecordRules());
    }

    /**
     * @return string[]
     */
    private function preCheckRules(): array
    {
        return [
            'preChecks.*'             => 'nullable|array',
            'preChecks.*.default'     => 'nullable|boolean',
            'preChecks.*.title'       => 'required|string|max:50',
            'preChecks.*.description' => 'nullable|string|max:1000',
        ];
    }

    /**
     * @return string[]
     */
    private function preCheckRecordRules(): array
    {
        return [
            'preChecks.*.pre_check_records.*'             => 'nullable|array',
            'preChecks.*.pre_check_records.*.short_title' => 'nullable|string|max:30',
            'preChecks.*.pre_check_records.*.title'       => 'required|string|max:50',
            'preChecks.*.pre_check_records.*.description' => 'nullable|string|max:1000',
            'preChecks.*.pre_check_records.*.record_type.key' => 'required|string|exists:record_types,key',
        ];
    }

    /**
     * @return array
     */
    public function messages(): array
    {
        return [
            'preChecks.*.title.required' => 'Pre-check title veld is verplicht',
            'preChecks.*.description.required' => 'Pre-check title veld is verplicht',
            'preChecks.*.pre_check_records.*.title.required' => 'Pre-check record title veld is verplicht',
            'preChecks.*.pre_check_records.*.record_type.key' => 'Pre-check record type key veld is verplicht',
        ];
    }
}
