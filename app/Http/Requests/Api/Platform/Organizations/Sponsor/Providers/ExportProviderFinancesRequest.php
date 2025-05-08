<?php

namespace App\Http\Requests\Api\Platform\Organizations\Sponsor\Providers;

use App\Exports\ProviderFinancesExport;

class ExportProviderFinancesRequest extends IndexProvidersRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules(): array
    {
        return array_merge(parent::rules(), [
            ...$this->exportableResourceRules(ProviderFinancesExport::getExportFieldsRaw()),
        ]);
    }
}
