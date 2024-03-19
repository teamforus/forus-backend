<?php

namespace App\Http\Requests\Api\Platform\Organizations\Sponsor\Vouchers\VoucherRecords;

use App\Http\Requests\BaseFormRequest;
use App\Models\Voucher;
use App\Models\VoucherRecord;
use App\Rules\VoucherRecordValueRule;
use Illuminate\Validation\Rule;

/**
 * @property-read Voucher $voucher
 */
abstract class BaseVoucherRecordRequest extends BaseFormRequest
{


    /**
     * @param VoucherRecord|null $record
     *
     * @return (VoucherRecordValueRule|string)[]
     *
     * @psalm-return list{'required', 'string', 'max:2000', VoucherRecordValueRule}
     */
    protected function recordValueRule(VoucherRecord $record = null): array
    {
        $recordTypeKey = $record ? $record->record_type->key : $this->string('record_type_key');

        return [
            'required',
            'string',
            'max:2000',
            new VoucherRecordValueRule($this->voucher, $recordTypeKey, $record),
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

    /**
     * @return string
     *
     * @psalm-return 'nullable|string|max:2000'
     */
    protected function recordNoteRule(): string
    {
        return 'nullable|string|max:2000';
    }
}
