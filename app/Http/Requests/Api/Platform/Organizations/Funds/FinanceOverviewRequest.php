<?php

namespace App\Http\Requests\Api\Platform\Organizations\Funds;

use App\Http\Requests\BaseFormRequest;
use App\Models\Organization;

/**
 * @property Organization $organization
 * @package App\Http\Requests\Api\Platform\Organizations\Funds
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
        return [
            'export_type'       => 'nullable|in:xls,csv',
            'detailed'          => 'nullable|boolean',
        ];
    }
}
