<?php

namespace App\Http\Requests\Api\Platform\Funds\Requests;

use App\Models\Employee;
use App\Models\FundRequest;
use App\Scopes\Builders\EmployeeQuery;
use App\Scopes\Builders\FundRequestRecordQuery;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Class UpdateFundRequestsRequest
 * @property FundRequest $fund_request
 * @package App\Http\Requests\Api\Platform\Funds\FundRequests
 */
class DeclineFundRequestsRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        $fund_request = $this->fund_request;

        $employeeAssignedRecords = FundRequestRecordQuery::whereIdentityIsAssignedEmployeeFilter(
            $fund_request->records()->getQuery(),
            auth_address()
        );

        return [
            'employee_id' => [
                'nullable',
                Rule::in($employeeAssignedRecords->pluck('validator_id')->toArray())
            ],
            'note' => [
                'nullable',
                'string:min5:1000'
            ]
        ];
    }
}
