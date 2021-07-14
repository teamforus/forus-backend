<?php

namespace App\Events\Vouchers;

use App\Models\Voucher;
use Illuminate\Queue\SerializesModels;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Broadcasting\InteractsWithSockets;

/**
 * Class VoucherCreated
 * @package App\Events\Vouchers
 */
class VoucherCreated
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    private $voucher;
    private $notifyRequesterAdded;
    private $notifyRequesterReserved;

    /**
     * Create a new event instance.
     *
     * @param Voucher $voucher
     * @param bool $notifyRequesterReserved
     * @param bool $notifyRequesterAdded
     */
    public function __construct(
        Voucher $voucher,
        bool $notifyRequesterReserved = true,
        bool $notifyRequesterAdded = true
    ) {
        $this->voucher = $voucher;
        $this->notifyRequesterAdded = $notifyRequesterAdded;
        $this->notifyRequesterReserved = $notifyRequesterReserved;
    }

    /**
     * Get the voucher
     *
     * @return Voucher
     */
    public function getVoucher(): Voucher
    {
        return $this->voucher;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return \Illuminate\Broadcasting\Channel
     */
    public function broadcastOn()
    {
        return new PrivateChannel('channel-name');
    }

    /**
     * @return bool
     */
    public function shouldNotifyRequesterReserved(): bool
    {
        return $this->notifyRequesterReserved;
    }

    /**
     * @return bool
     */
    public function shouldNotifyRequesterAdded(): bool
    {
        return $this->notifyRequesterAdded;
    }
}
