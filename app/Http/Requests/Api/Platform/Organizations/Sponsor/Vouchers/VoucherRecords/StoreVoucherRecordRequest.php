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
     * @return (array|string)[]
     *
     * @psalm-return array{note: string, value: array, record_type_key: array}
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
     * @return (\Illuminate\Validation\Rules\Exists|string)[]
     *
     * @psalm-return list{'required', \Illuminate\Validation\Rules\Exists}
     */
    protected function recordTypeKeyRule(): array
    {
        return [
            'required',
            Rule::exists('record_types', 'key')->where('vouchers', true),
        ];
    }
}
