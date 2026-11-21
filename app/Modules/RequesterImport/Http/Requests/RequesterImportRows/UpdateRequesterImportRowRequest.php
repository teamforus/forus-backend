<?php

namespace App\Modules\RequesterImport\Http\Requests\RequesterImportRows;

use App\Http\Requests\BaseFormRequest;
use App\Models\RecordType;
use App\Modules\RequesterImport\Rules\RequesterImportRecordTypesRule;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class UpdateRequesterImportRowRequest extends BaseFormRequest
{
    /**
     * @return bool
     */
    public function authorize(): bool
    {
        return Gate::allows('update', [$this->route('requesterImportRow'), $this->getRouteOrganization()]);
    }

    /**
     * @return array
     */
    public function rules(): array
    {
        $recordTypes = RecordType::searchRecordTypes(
            organizationId: $this->getRouteOrganization()->id,
            requesterImports: true,
        );

        return [
            'bsn' => 'missing',
            'records.bsn' => 'missing',
            'records' => [
                'bail', 'required', Rule::array($recordTypes->keys()->all()), 'min:1',
                new RequesterImportRecordTypesRule($recordTypes, batch: false),
            ],
        ];
    }
}
