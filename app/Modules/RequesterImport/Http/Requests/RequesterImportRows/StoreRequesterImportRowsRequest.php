<?php

namespace App\Modules\RequesterImport\Http\Requests\RequesterImportRows;

use App\Http\Requests\BaseFormRequest;
use App\Models\RecordType;
use App\Modules\RequesterImport\Models\RequesterImportRow;
use App\Modules\RequesterImport\Rules\RequesterImportRecordTypesRule;
use App\Modules\RequesterImport\Rules\RequesterImportRowRule;
use App\Rules\BsnRule;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class StoreRequesterImportRowsRequest extends BaseFormRequest
{
    /**
     * @return bool
     */
    public function authorize(): bool
    {
        return Gate::allows('create', [RequesterImportRow::class, $this->getRouteOrganization()]);
    }

    /**
     * @return array
     */
    public function rules(): array
    {
        $organization = $this->getRouteOrganization();

        $recordTypes = RecordType::searchRecordTypes(
            organizationId: $organization->id,
            requesterImports: true,
        );

        return [
            'rows' => [
                'bail', 'required', 'array', 'list', 'min:1', 'max:1000', new RequesterImportRecordTypesRule($recordTypes),
            ],
            'rows.*' => [
                'bail', 'required', Rule::array(['bsn', ...$recordTypes->keys()->all()]),
                new RequesterImportRowRule($organization),
            ],
            'rows.*.bsn' => [
                'bail', 'required', 'string', new BsnRule(exactlyNineDigits: true), 'distinct:strict',
            ],
            'rows.*.client_number' => 'bail|nullable|string|max:400|distinct:strict',
        ];
    }
}
