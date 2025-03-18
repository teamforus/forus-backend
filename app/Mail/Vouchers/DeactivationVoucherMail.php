<?php

namespace App\Mail\Vouchers;

use App\Mail\ImplementationMail;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use League\CommonMark\Exception\CommonMarkException;

class DeactivationVoucherMail extends ImplementationMail
{
    use Queueable;
    use SerializesModels;

    public ?string $notificationTemplateKey = 'notifications_identities.voucher_deactivated';

    /**
     * @throws CommonMarkException
     * @return Mailable
     */
    public function build(): Mailable
    {
        return $this->buildNotificationTemplatedMail();
    }
}
