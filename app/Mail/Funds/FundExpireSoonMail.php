<?php

namespace App\Mail\Funds;

use App\Mail\ImplementationMail;
use Illuminate\Mail\Mailable;

/**
 * Class FundExpiredMail
 * @package App\Mail\Funds
 */
class FundExpireSoonMail extends ImplementationMail
{
    protected $notificationTemplateKey = 'notifications_identities.voucher_expire_soon_budget';

    public function build(): Mailable
    {
        return $this->buildTemplatedNotification([
            'link_webshop' => $this->makeLink($this->mailData['link_webshop'], $this->mailData['link_webshop'])
        ]);
    }
}
