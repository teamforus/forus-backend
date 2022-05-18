<?php

namespace App\Mail\Vouchers;

use App\Mail\ImplementationMail;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

/**
 * Class DeactivationVoucherMail
 * @package App\Mail\Vouchers
 */
class DeactivationVoucherMail extends ImplementationMail
{
    use Queueable, SerializesModels;

    protected string $notificationTemplateKey = 'notifications_identities.voucher_deactivated';

    /**
     * @return Mailable
     */
    public function build(): Mailable
    {
        return $this->buildNotificationTemplatedMail();
    }
}