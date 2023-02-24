<?php

namespace App\Http\Requests\Api\Platform\Organizations\Sponsor\Vouchers\VoucherRecords;

class IndexVoucherRecordRequest extends BaseVoucherRecordRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return $this->sortableResourceRules(100, [
            'id', 'record_type_name', 'value', 'note', 'created_at',
        ]);
    }
}
