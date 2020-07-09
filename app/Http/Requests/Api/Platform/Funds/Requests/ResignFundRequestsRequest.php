<?php

namespace App\Http\Requests\Api\Platform\Funds\Requests;

use App\Models\FundRequest;
use App\Models\Organization;
use App\Scopes\Builders\FundRequestRecordQuery;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Class UpdateFundRequestsRequest
 * @property Organization $organization
 * @property FundRequest $fund_request
 * @package App\Http\Requests\Api\Platform\Funds\FundRequests
 */
class ResignFundRequestsRequest extends FormRequest
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
        $fund_request = $this->fund_request;

        $employeeAssignedRecords = FundRequestRecordQuery::whereIdentityIsAssignedEmployeeFilter(
            $fund_request->records()->getQuery(),
            auth_address(true),
            $this->organization->findEmployee(auth_address(true))->id
        );

        return [
            'employee_id' => [
                'nullable',
                Rule::in($employeeAssignedRecords->pluck('employee_id')->toArray())
            ]
        ];
    }
}
