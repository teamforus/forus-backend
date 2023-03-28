<?php

namespace App\Rules;

use App\Models\RecordType;
use App\Models\Voucher;
use App\Models\VoucherRecord;

class VoucherRecordValueRule extends BaseRule
{
    /**
     * @var Voucher
     */
    protected Voucher $voucher;

    /**
     * @var VoucherRecord|null
     */
    protected ?VoucherRecord $record = null;

    /**
     * @var string
     */
    protected string $record_type_key;

    /**
     * Create a new rule instance.
     *
     * @return void
     */
    public function __construct(
        Voucher $voucher,
        string $record_type_key,
        ?VoucherRecord $record = null
    ) {
        $this->record = $record;
        $this->voucher = $voucher;
        $this->record_type_key = $record_type_key;
    }

    /**
     * Determine if the validation rule passes.
     *
     * @param  string  $attribute
     * @param  mixed  $value
     * @return bool
     */
    public function passes($attribute, $value): bool
    {
        /** @var VoucherRecord $record */
        $recordType = RecordType::findByKey($this->record_type_key);
        $validationError = VoucherRecord::validateRecord($this->record_type_key, $value);
        $record = $this->voucher->voucher_records()->where('record_type_id', $recordType?->id)->first();

        if (!$recordType?->vouchers) {
            return $this->reject('Invalid type');
        }

        if ($record && ($record->id !== $this->record?->id)) {
            return $this->reject('Record of this type already exists');
        }

        return !$validationError || $this->reject($validationError);
    }
}
