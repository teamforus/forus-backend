<?php

namespace App\Events\VoucherRecords;

use App\Models\VoucherRecord;
use Illuminate\Queue\SerializesModels;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Broadcasting\InteractsWithSockets;

abstract class BaseVoucherRecordEvent
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    protected VoucherRecord $voucherRecord;

    /**
     * Create a new event instance.
     *
     * @param VoucherRecord $voucherRecord
     */
    public function __construct(VoucherRecord $voucherRecord)
    {
        $this->voucherRecord = $voucherRecord;
    }

    /**
     * @return VoucherRecord
     * @noinspection PhpUnused
     */
    public function getVoucherRecord(): VoucherRecord
    {
        return $this->voucherRecord;
    }
}
