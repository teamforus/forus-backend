<?php

namespace App\Http\Requests\Api\Platform\Organizations;

use App\Http\Requests\BaseFormRequest;

/**
 * Class UpdateBankStatementFieldsRequest
 */
class UpdateBankStatementFieldsRequest extends BaseFormRequest
{


    /**
     * Get the validation rules that apply to the request.
     *
     * @return string[]
     *
     * @psalm-return array{bank_transaction_id: 'nullable|boolean', bank_transaction_date: 'nullable|boolean', bank_reservation_number: 'nullable|boolean', bank_branch_number: 'nullable|boolean', bank_branch_id: 'nullable|boolean', bank_branch_name: 'nullable|boolean', bank_fund_name: 'nullable|boolean', bank_note: 'nullable|boolean'}
     */
    public function rules(): array
    {
        return [
            'bank_transaction_id' => 'nullable|boolean',
            'bank_transaction_date' => 'nullable|boolean',
            'bank_reservation_number' => 'nullable|boolean',
            'bank_branch_number' => 'nullable|boolean',
            'bank_branch_id' => 'nullable|boolean',
            'bank_branch_name' => 'nullable|boolean',
            'bank_fund_name' => 'nullable|boolean',
            'bank_note' => 'nullable|boolean',
        ];
    }
}
