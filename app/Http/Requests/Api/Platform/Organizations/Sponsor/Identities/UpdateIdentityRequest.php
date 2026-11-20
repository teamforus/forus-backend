<?php

namespace App\Http\Requests\Api\Platform\Organizations\Sponsor\Identities;

use App\Http\Requests\BaseFormRequest;
use App\Models\RecordType;
use App\Rules\ProfileRecordValueRule;
use Illuminate\Validation\Rule;

class UpdateIdentityRequest extends BaseFormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize(): bool
    {
        return $this->getRouteOrganization()->allow_profiles;
    }

    /**
     * @return array
     */
    public function rules(): array
    {
        $recordTypes = RecordType::searchRecordTypes(
            organizationId: $this->getRouteOrganization()->id,
            profileRecords: true,
            excludeKeys: [RecordType::KEY_BSN],
        );

        return [
            'records' => ['sometimes', 'array', 'list'],
            'records.*' => ['bail', 'required', 'array:record_type_key,value'],
            'records.*.record_type_key' => [
                'bail', 'required', 'string', 'distinct:strict', Rule::in($recordTypes->keys()->all()),
            ],
            'records.*.value' => Rule::forEach(function (
                mixed $value,
                string $attribute,
                array $data,
                mixed $record,
            ) use ($recordTypes): array {
                $key = is_array($record) ? ($record['record_type_key'] ?? null) : null;
                $recordType = is_string($key) ? $recordTypes->get($key) : null;

                return ['present', 'nullable', ...($recordType ? [new ProfileRecordValueRule($recordType)] : [])];
            }),
        ];
    }

    /**
     * @return array<string, string|null>
     */
    public function profileRecords(): array
    {
        return array_column($this->validated('records', []), 'value', 'record_type_key');
    }
}
