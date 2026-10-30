<?php

namespace App\Modules\RequesterImport\Http\Requests\RequesterImportRows;

use App\Http\Requests\BaseFormRequest;
use App\Models\RecordType;
use App\Modules\RequesterImport\Models\RequesterImportRow;
use App\Modules\RequesterImport\Rules\RequesterImportRecordTypesRule;
use App\Modules\RequesterImport\Rules\RequesterImportRowRule;
use App\Rules\BsnRule;
use App\Searches\RecordTypeSearch;
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
        $organizationId = $this->getRouteOrganization()->id;

        $search = new RecordTypeSearch([
            'requester_imports' => true,
            'organization_id' => $organizationId,
        ], RecordType::query());

        $recordTypes = $search->query()->with('record_type_options.translations')->get()->keyBy('key');

        return [
            'rows' => [
                'bail', 'required', 'array', 'list', 'min:1', 'max:1000', new RequesterImportRecordTypesRule($recordTypes),
            ],
            'rows.*' => [
                'bail', 'required', Rule::array(['bsn', ...$recordTypes->keys()->all()]),
                new RequesterImportRowRule($organizationId),
            ],
            'rows.*.bsn' => [
                'bail', 'required', 'string', new BsnRule(exactlyNineDigits: true), 'distinct:strict',
            ],
            'rows.*.client_uuid' => 'bail|nullable|string|max:400|distinct:strict',
        ];
    }
}
