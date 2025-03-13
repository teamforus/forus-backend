<?php

namespace App\Events\VoucherRecords;

use App\Models\VoucherRecord;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

abstract class BaseVoucherRecordEvent
{
    use Dispatchable;
    use InteractsWithSockets;
    use SerializesModels;

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
