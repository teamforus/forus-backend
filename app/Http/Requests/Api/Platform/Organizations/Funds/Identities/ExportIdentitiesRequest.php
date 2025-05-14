<?php

namespace App\Http\Requests\Api\Platform\Organizations\Funds\Identities;

use App\Exports\FundIdentitiesExport;

class ExportIdentitiesRequest extends IndexIdentitiesRequest
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
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return array_merge(parent::rules(), [
            'per_page' => 'nullable',
            ...$this->exportableResourceRules(FundIdentitiesExport::getExportFieldsRaw()),
        ]);
    }
}
