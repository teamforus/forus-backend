<?php

namespace App\Http\Requests\Api\Platform\Organizations\Sponsor\Vouchers\VoucherRecords;

use App\Models\Voucher;
use Illuminate\Validation\Rule;

/**
 * @property-read Voucher $voucher
 */
class StoreVoucherRecordRequest extends BaseVoucherRecordRequest
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
            'value' => $this->recordValueRule(),
            'record_type_key' => $this->recordTypeKeyRule(),
        ];
    }

    /**
     * @return array
     */
    protected function recordTypeKeyRule(): array
    {
        return [
            'required',
            Rule::exists('record_types', 'key')->where('vouchers', true),
        ];
    }
}
