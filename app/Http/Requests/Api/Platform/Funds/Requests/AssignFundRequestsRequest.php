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
class AssignFundRequestsRequest extends FormRequest
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

        $employee_id = $this->input('employee_id', null);
        $employee = Employee::find($employee_id);
        $identity_address = auth_address();

        $identityEmployees = EmployeeQuery::whereHasPermissionFilter(
            Employee::query(),
            'validate_records'
        )->where(compact('identity_address'))->pluck('id')->toArray();

        $hasRecordsAvailable = $employee ? FundRequestRecordQuery::whereIdentityCanBeValidatorFilter(
            $fund_request->records()->getQuery(),
            auth_address(),
            $employee_id
        )->exists() : false;

        log_debug([$identityEmployees, $hasRecordsAvailable]);

        return [
            'employee_id' => [
                'nullable',
                Rule::in($hasRecordsAvailable ? $identityEmployees : [])
            ]
        ];
    }
}
