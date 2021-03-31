<?php

namespace App\Mail\Funds;

use App\Mail\ImplementationMail;
use App\Services\Forus\Notification\EmailFrom;
use Illuminate\Mail\Mailable;

/**
 * Class FundClosed
 * @package App\Mail\Funds
 */
class FundClosed extends ImplementationMail
{
    private $fundName;
    private $fundContact;
    private $sponsorName;
    private $link;

    public $communicationType;

    /**
     * Create a new message instance.
     *
     * FundClosed constructor.
     * @param string $fundName
     * @param $fundContact
     * @param $sponsor_name
     * @param $link
     * @param EmailFrom|null $emailFrom
     */
    public function __construct(
        string $fundName,
        $fundContact,
        $sponsor_name,
        $link,
        ?EmailFrom $emailFrom
    ) {
        $this->setMailFrom($emailFrom);

        $this->fundName     = $fundName;
        $this->link         = $link;
        $this->fundContact  = $fundContact;
        $this->sponsorName  = $sponsor_name;
    }

    /**
     * Build the message.
     *
     * @return Mailable
     */
    public function build(): Mailable
    {
        $this->communicationType =  $this->emailFrom->isInformalCommunication() ? 'informal' : 'formal';

        return $this->buildBase()
            ->subject(mail_trans('fund_closed.title_' . $this->communicationType, [
                'fund_name' => $this->fundName
            ]))
            ->view('emails.funds.fund_closed', [
                'sponsor_name'  => $this->sponsorName,
                'fund_name'     => $this->fundName,
                'fund_contact'  => $this->fundContact,
                'webshop_link'  => $this->link
            ]);
    }
}
