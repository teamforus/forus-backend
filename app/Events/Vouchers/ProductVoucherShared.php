<?php

namespace App\Events\Vouchers;

use App\Models\Product;
use App\Models\Voucher;
use Illuminate\Queue\SerializesModels;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Broadcasting\InteractsWithSockets;

class ProductVoucherShared
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    private $voucher;
    private $message;
    private $sendCopyToUser;

    /**
     * Create a new event instance.
     *
     * ProductVoucherShared constructor.
     * @param Voucher $voucher
     * @param string $message
     * @param $sendCopyToUser
     */
    public function __construct(
        Voucher $voucher,
        string $message,
        bool $sendCopyToUser
    ) {
        $this->voucher = $voucher;
        $this->message = $message;
        $this->sendCopyToUser = $sendCopyToUser;
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
     * Get the voucher product
     *
     * @return Product
     */
    public function getProduct(): Product
    {
        return $this->voucher->product;
    }

    /**
     * Get the share message product
     *
     * @return string
     */
    public function getMessage(): string
    {
        return $this->message;
    }

    /**
     * @return bool
     */
    public function isSendCopyToUser(): bool
    {
        return $this->sendCopyToUser;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return \Illuminate\Broadcasting\Channel|array
     */
    public function broadcastOn()
    {
        return new PrivateChannel('channel-name');
    }
}
