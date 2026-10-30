<?php

namespace App\Modules\RequesterImport\Http\Requests\RequesterImportRows;

use App\Http\Requests\BaseFormRequest;
use App\Models\RecordType;
use App\Modules\RequesterImport\Rules\RequesterImportRecordTypesRule;
use App\Searches\RecordTypeSearch;
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
        $search = new RecordTypeSearch([
            'requester_imports' => true,
            'organization_id' => $this->getRouteOrganization()->id,
        ], RecordType::query());

        $recordTypes = $search->query()->with('record_type_options.translations')->get()->keyBy('key');

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
