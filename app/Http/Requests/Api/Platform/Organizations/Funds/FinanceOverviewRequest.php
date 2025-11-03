<?php

namespace App\Http\Requests\Api\Platform\Organizations\Funds;

use App\Exports\FundsExport;
use App\Exports\FundsExportDetailed;
use App\Http\Requests\BaseFormRequest;
use App\Models\Organization;

/**
 * @property Organization $organization
 */
class FinanceOverviewRequest extends BaseFormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize(): bool
    {
        return $this->organization->identityCan($this->identity(), 'view_finances');
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules(): array
    {
        $fields = $this->get('detailed', false)
            ? FundsExportDetailed::getExportFieldsRaw()
            : FundsExport::getExportFieldsRaw();

        return [
            'detailed' => 'nullable|boolean',
            'year' => 'nullable|integer',
            ...$this->exportableResourceRules($fields),
        ];
    }
}
