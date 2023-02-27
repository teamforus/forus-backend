<?php

namespace App\Http\Requests\Api\Platform\Organizations\Sponsor\Vouchers\VoucherRecords;


use App\Models\VoucherRecord;

/**
 * @property-read VoucherRecord $voucher_record
 */
class UpdateVoucherRecordRequest extends BaseVoucherRecordRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'note' => $this->recordNoteRule(),
            'value' => $this->recordValueRule($this->voucher_record),
        ];
    }
}
