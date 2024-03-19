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
     * Get the validation rules that apply to the request.
     *
     * @return string[]
     *
     * @psalm-return array{export_type: 'nullable|in:xls,csv', detailed: 'nullable|boolean'}
     */
    public function rules(): array
    {
        return [
            'export_type'       => 'nullable|in:xls,csv',
            'detailed'          => 'nullable|boolean',
        ];
    }
}
