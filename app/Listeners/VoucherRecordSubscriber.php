<?php

namespace App\Listeners;

use App\Events\VoucherRecords\VoucherRecordCreated;
use App\Events\VoucherRecords\VoucherRecordDeleted;
use App\Events\VoucherRecords\VoucherRecordUpdated;
use Illuminate\Events\Dispatcher;

class VoucherRecordSubscriber
{
    /**
     * @param VoucherRecordCreated $event
     * @throws \Exception
     * @noinspection PhpUnused
     */
    public function onVoucherRecordCreated(VoucherRecordCreated $event): void
    {
        $voucherRecord = $event->getVoucherRecord();

        $voucherRecord->log($voucherRecord::EVENT_CREATED, [
            'voucher' => $voucherRecord->voucher,
            'voucher_record' => $voucherRecord,
        ]);
    }

    /**
     * @param VoucherRecordUpdated $event
     * @throws \Exception
     * @noinspection PhpUnused
     */
    public function onVoucherRecordUpdated(VoucherRecordUpdated $event): void
    {
        $voucherRecord = $event->getVoucherRecord();

        $voucherRecord->log($voucherRecord::EVENT_UPDATED, [
            'voucher' => $voucherRecord->voucher,
            'voucher_record' => $voucherRecord,
        ]);
    }

    /**
     * @param VoucherRecordDeleted $event
     * @throws \Exception
     * @noinspection PhpUnused
     */
    public function VoucherRecordDeleted(VoucherRecordDeleted $event): void
    {
        $voucherRecord = $event->getVoucherRecord();

        $voucherRecord->log($voucherRecord::EVENT_DELETED, [
            'voucher' => $voucherRecord->voucher,
            'voucher_record' => $voucherRecord,
        ]);
    }

    /**
     * The events dispatcher
     *
     * @param Dispatcher $events
     * @return void
     * @noinspection PhpUnused
     */
    public function subscribe(Dispatcher $events): void
    {
        $class = '\\' . static::class;

        $events->listen(VoucherRecordCreated::class, "$class@onVoucherRecordCreated");
        $events->listen(VoucherRecordUpdated::class, "$class@onVoucherRecordUpdated");
        $events->listen(VoucherRecordDeleted::class, "$class@VoucherRecordDeleted");
    }
}
