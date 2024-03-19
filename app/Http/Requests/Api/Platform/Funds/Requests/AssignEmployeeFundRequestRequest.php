<?php

namespace App\Http\Requests\Api\Platform\Funds\Requests;

use App\Http\Requests\BaseFormRequest;
use App\Models\FundRequest;
use App\Models\Organization;
use App\Scopes\Builders\EmployeeQuery;

/**
 * @property-read Organization $organization
 * @property-read FundRequest $fund_request
 */
class AssignEmployeeFundRequestRequest extends BaseFormRequest
{


    /**
     * Get the validation rules that apply to the request.
     *
     * @return string[]
     *
     * @psalm-return array{employee_id: string}
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
        $recordsQuery = $this->fund_request->records_pending()->whereDoesntHave('employee');

        return EmployeeQuery::whereCanValidateRecords(
            $this->organization->employees(),
            $recordsQuery->select('fund_request_records.id')->getQuery()
        )->pluck('id')->toArray();
    }
}
