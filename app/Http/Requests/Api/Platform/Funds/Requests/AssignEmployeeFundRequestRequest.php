<?php

namespace App\Http\Requests\Api\Platform\Funds\Requests;

use App\Http\Requests\BaseFormRequest;
use App\Models\FundRequest;
use App\Models\Organization;
use App\Models\Permission;
use App\Scopes\Builders\EmployeeQuery;
use Illuminate\Database\Eloquent\Builder;

/**
 * @property-read Organization $organization
 * @property-read FundRequest $fund_request
 */
class AssignEmployeeFundRequestRequest extends BaseFormRequest
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
     * @return array
     */
    public function rules(): array
    {
        return [
            'employee_id' => 'required|in:' . implode(',', $this->validEmployeeId()),
        ];
    }

    /**
     * @return array
     */
    protected function validEmployeeId(): array
    {
        return $this->organization
            ->employees()
            ->where('id', '!=', $this->fund_request->employee_id)
            ->where(fn (Builder $builder) => EmployeeQuery::whereHasPermissionFilter($builder, [
                Permission::VALIDATE_RECORDS,
            ]))
            ->pluck('id')
            ->toArray();
    }
}
