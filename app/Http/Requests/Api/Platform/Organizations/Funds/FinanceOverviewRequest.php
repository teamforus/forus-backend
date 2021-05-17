<?php

namespace App\Http\Requests\Api\Platform\Organizations\Funds;

use App\Http\Requests\BaseFormRequest;
use App\Models\Organization;

/**
 * Class FinanceOverviewRequest
 * @property Organization $organization
 * @package App\Http\Requests\Api\Platform\Organizations\Funds
 * @noinspection PhpUnused
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
        return $this->organization->identityCan($this->auth_address(), 'view_finances');
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
